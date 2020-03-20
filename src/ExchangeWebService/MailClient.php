<?php declare (strict_types=1);

namespace JanMikes\Slacker\ExchangeWebService;

use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseItemIdsType;
use jamesiarmes\PhpEws\Autodiscover;
use jamesiarmes\PhpEws\Client;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Enumeration\ResponseClassType;
use jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use jamesiarmes\PhpEws\Request\FindItemType;
use jamesiarmes\PhpEws\Request\GetItemType;
use jamesiarmes\PhpEws\Type\AndType;
use jamesiarmes\PhpEws\Type\ConstantValueType;
use jamesiarmes\PhpEws\Type\ContainsExpressionType;
use jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use jamesiarmes\PhpEws\Type\FieldURIOrConstantType;
use jamesiarmes\PhpEws\Type\IsEqualToType;
use jamesiarmes\PhpEws\Type\IsGreaterThanOrEqualToType;
use jamesiarmes\PhpEws\Type\ItemIdType;
use jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use jamesiarmes\PhpEws\Type\RestrictionType;
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


	public function __construct(
		string $exchangeEmail,
		string $exchangeUser,
		string $exchangePassword,
		string $messageSubject,
		string $messageSender
	)
	{
		$attempts = 0;
		do {
			if ($attempts >= 5) {
				throw new \RuntimeException('Could not autodiscover exchange settings from credentials.');
			}

			$attempts++;
			$client = Autodiscover::getEWS($exchangeEmail, $exchangePassword, $exchangeUser);
		} while (!$client);

		$this->client = $client;
		$this->messageSubject = $messageSubject;
		$this->messageSender = $messageSender;
	}


	/**
	 * @param string[] $messagesIds
	 *
	 * return string[]
	 */
	public function getBodies(array $messagesIds): array
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
				$bodies[$message->ItemId->Id] = $message->Body->_;
			}
		}

		return $bodies;
	}


	/**
	 * @return string[]
	 */
	public function findMessagesIds(): array
	{
		// @TODO: find only unread items

		$request = new FindItemType();
		$request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();

		$subject = new ContainsExpressionType();
		$subject->FieldURI = new PathToUnindexedFieldType();
		$subject->FieldURI->FieldURI = UnindexedFieldURIType::ITEM_SUBJECT;
		$subject->Constant = new ConstantValueType();
		$subject->Constant->Value = $this->messageSubject;

		$sender = new IsEqualToType();
		$sender->FieldURI = new PathToUnindexedFieldType();
		$sender->FieldURI->FieldURI = UnindexedFieldURIType::MESSAGE_SENDER;
		$sender->FieldURIOrConstant = new FieldURIOrConstantType();
		$sender->FieldURIOrConstant->Constant = new ConstantValueType();
		$sender->FieldURIOrConstant->Constant->Value = $this->messageSender;

		// Build the start date restriction.
		$startDate = new IsGreaterThanOrEqualToType();
		$startDate->FieldURI = new PathToUnindexedFieldType();
		$startDate->FieldURI->FieldURI = UnindexedFieldURIType::ITEM_DATE_TIME_RECEIVED;
		$startDate->FieldURIOrConstant = new FieldURIOrConstantType();
		$startDate->FieldURIOrConstant->Constant = new ConstantValueType();
		$startDate->FieldURIOrConstant->Constant->Value = (new \DateTimeImmutable('yesterday'))->format('c');

		// Build the restriction.
		$request->Restriction = new RestrictionType();
		$request->Restriction->And = new AndType();
		$request->Restriction->And->IsGreaterThanOrEqualTo = $startDate;
		$request->Restriction->And->Contains = $subject;
		$request->Restriction->And->IsEqualTo = $sender;

		// Return mode - just ids.
		$request->ItemShape = new ItemResponseShapeType();
		$request->ItemShape->BaseShape = DefaultShapeNamesType::ID_ONLY;

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


	public function markMessageAsRead(string $messageId): void
	{

	}
}
