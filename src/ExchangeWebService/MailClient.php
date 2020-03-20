<?php declare (strict_types=1);

namespace JanMikes\Slacker\ExchangeWebService;

use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseItemIdsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfItemChangeDescriptionsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfItemChangesType;
use jamesiarmes\PhpEws\Autodiscover;
use jamesiarmes\PhpEws\Client;
use jamesiarmes\PhpEws\Enumeration\ConflictResolutionType;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Enumeration\MessageDispositionType;
use jamesiarmes\PhpEws\Enumeration\ResponseClassType;
use jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use jamesiarmes\PhpEws\Request\FindItemType;
use jamesiarmes\PhpEws\Request\GetItemType;
use jamesiarmes\PhpEws\Request\UpdateItemType;
use jamesiarmes\PhpEws\Type\AndType;
use jamesiarmes\PhpEws\Type\ConstantValueType;
use jamesiarmes\PhpEws\Type\ContainsExpressionType;
use jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use jamesiarmes\PhpEws\Type\FieldURIOrConstantType;
use jamesiarmes\PhpEws\Type\IsEqualToType;
use jamesiarmes\PhpEws\Type\IsGreaterThanOrEqualToType;
use jamesiarmes\PhpEws\Type\ItemChangeType;
use jamesiarmes\PhpEws\Type\ItemIdType;
use jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use jamesiarmes\PhpEws\Type\MessageType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use jamesiarmes\PhpEws\Type\RestrictionType;
use jamesiarmes\PhpEws\Type\SetItemFieldType;
use JanMikes\Slacker\ExchangeWebService\Exceptions\ExchangeWebServiceException;

final class MailClient
{
	/**
	 * @var Client
	 */
	private $client;

	/**
	 * @var string
	 */
	private $messageSubject;

	/**
	 * @var string
	 */
	private $messageSender;

	/**
	 * @var RestrictionsFactory
	 */
	private $restrictionsFactory;


	public function __construct(
		string $exchangeEmail,
		string $exchangeUser,
		string $exchangePassword,
		string $messageSubject,
		string $messageSender,
		RestrictionsFactory $restrictionsFactory
	)
	{
		$attempts = 0;
		do {
			if ($attempts >= 5) {
				throw new \RuntimeException('Could not autodiscover exchange settings from credentials.');
			}

			$attempts++;
			$client = Autodiscover::getEWS($exchangeEmail, $exchangePassword, $exchangeUser);

			if (!$client) {
				sleep(3);
			}
		} while (!$client);

		$this->client = $client;
		$this->messageSubject = $messageSubject;
		$this->messageSender = $messageSender;
		$this->restrictionsFactory = $restrictionsFactory;
	}


	/**
	 * @param string[] $messagesIds
	 *
	 * @return MessageType[]
	 */
	public function getMessages(array $messagesIds): array
	{
		if (empty($messagesIds)) {
			return [];
		}

		$request = new GetItemType();
		$request->ItemIds = new NonEmptyArrayOfBaseItemIdsType();
		$request->ItemIds->ItemId = array_map(static function(string $messageId) {
			$itemId = new ItemIdType();
			$itemId->Id = $messageId;

			return $itemId;
		}, $messagesIds);

		$request->ItemShape = new ItemResponseShapeType();
		$request->ItemShape->BaseShape = DefaultShapeNamesType::DEFAULT_PROPERTIES;

		$response = $this->client->GetItem($request);

		$bodies = [];

		// Iterate over the results, printing any error messages or message subjects.
		foreach ($response->ResponseMessages->GetItemResponseMessage as $getItem) {
			// Make sure the request succeeded.
			if ($getItem->ResponseClass !== ResponseClassType::SUCCESS) {
				$code = $getItem->ResponseCode;
				$message = $getItem->MessageText;

				throw new ExchangeWebServiceException(sprintf(
					'Failed to get messages with %s: %s',
					$code,
					$message
				));
			}

			foreach ($getItem->Items->Message as $message) {
				$bodies[$message->ItemId->Id] = $message;
			}
		}

		return $bodies;
	}


	/**
	 * @return string[]
	 */
	public function findUnreadMessages(): array
	{
		$request = new FindItemType();
		$request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();

		$request->Restriction = $this->restrictionsFactory->createRestrictions(
			$this->messageSubject,
			$this->messageSender
		);

		$request->ItemShape = new ItemResponseShapeType();
		$request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;

		// Search in the user's inbox.
		$folder = new DistinguishedFolderIdType();
		$folder->Id = DistinguishedFolderIdNameType::INBOX;
		$request->ParentFolderIds->DistinguishedFolderId[] = $folder;

		$response = $this->client->FindItem($request);
		$ids = [];

		// Iterate over the results, printing any error messages or message subjects.
		foreach ($response->ResponseMessages->FindItemResponseMessage as $foundItem) {
			// Make sure the request succeeded.
			if ($foundItem->ResponseClass !== ResponseClassType::SUCCESS) {
				$code = $foundItem->ResponseCode;
				$message = $foundItem->MessageText;

				throw new ExchangeWebServiceException(sprintf(
					'Failed to search messages with %s: %s',
					$code,
					$message
				));
			}

			// Iterate over the messages that were found, printing the subject for each.
			$items = $foundItem->RootFolder->Items->Message;
			foreach ($items as $item) {
				$ids[] = $item->ItemId->Id;
			}
		}

		return $ids;
	}


	public function markMessageAsRead(MessageType $message): void
	{
		$request = new UpdateItemType();
		$request->ConflictResolution = ConflictResolutionType::ALWAYS_OVERWRITE;
		$request->MessageDisposition = MessageDispositionType::SAVE_ONLY;
		$request->ItemChanges = new NonEmptyArrayOfItemChangesType();

		$readChange = new ItemChangeType();
		$readChange->ItemId = $message->ItemId;
		$readChange->Updates = new NonEmptyArrayOfItemChangeDescriptionsType();

		$setItemFieldType = new SetItemFieldType();
		$setItemFieldType->FieldURI = new PathToUnindexedFieldType();
		$setItemFieldType->FieldURI->FieldURI = UnindexedFieldURIType::MESSAGE_IS_READ;
		$setItemFieldType->Message = new MessageType();
		$setItemFieldType->Message->IsRead = true;

		$readChange->Updates->SetItemField[] = $setItemFieldType;

		$request->ItemChanges->ItemChange[] = $readChange;

		$response = $this->client->UpdateItem($request);

		// Iterate over the results, printing any error messages or message subjects.
		foreach ($response->ResponseMessages->UpdateItemResponseMessage as $updatedItem) {
			// Make sure the request succeeded.
			if ($updatedItem->ResponseClass !== ResponseClassType::SUCCESS) {
				$code = $updatedItem->ResponseCode;
				$resultMessage = $updatedItem->MessageText;

				throw new ExchangeWebServiceException(sprintf(
					'Failed to update message with %s: %s',
					$code,
					$resultMessage
				));
			}
		}
	}
}
