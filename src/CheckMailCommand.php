<?php declare (strict_types=1);

namespace JanMikes\Slacker;

use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use jamesiarmes\PhpEws\Autodiscover;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Enumeration\ResponseClassType;
use jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use jamesiarmes\PhpEws\Request\FindItemType;
use jamesiarmes\PhpEws\Type\AndType;
use jamesiarmes\PhpEws\Type\ConstantValueType;
use jamesiarmes\PhpEws\Type\ContainsExpressionType;
use jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use jamesiarmes\PhpEws\Type\FieldURIOrConstantType;
use jamesiarmes\PhpEws\Type\IsEqualToType;
use jamesiarmes\PhpEws\Type\IsGreaterThanOrEqualToType;
use jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use jamesiarmes\PhpEws\Type\QueryStringType;
use jamesiarmes\PhpEws\Type\RestrictionType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CheckMailCommand extends Command
{
	/**
	 * @var string
	 */
	private $email;

	/**
	 * @var string
	 */
	private $user;

	/**
	 * @var string
	 */
	private $password;

	/**
	 * @var string
	 */
	private $subject;

	/**
	 * @var string
	 */
	private $sender;


	public function __construct(
		string $email,
		string $user,
		string $password,
		string $subject,
		string $sender
	)
	{
		$this->email = $email;
		$this->user = $user;
		$this->password = $password;
		$this->subject = $subject;
		$this->sender = $sender;

		parent::__construct();
	}


	protected function configure(): void
	{
		$this->setName('check-mail');
	}


	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$client = Autodiscover::getEWS($this->email, $this->password, $this->user);

		$request = new FindItemType();
		$request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();

		$start_date = new \DateTimeImmutable('yesterday');

		$subject = new ContainsExpressionType();
		$subject->FieldURI = new PathToUnindexedFieldType();
		$subject->FieldURI->FieldURI = UnindexedFieldURIType::ITEM_SUBJECT;
		$subject->Constant = new ConstantValueType();
		$subject->Constant->Value = $this->subject;

		$sender = new IsEqualToType();
		$sender->FieldURI = new PathToUnindexedFieldType();
		$sender->FieldURI->FieldURI = UnindexedFieldURIType::MESSAGE_SENDER;
		$sender->FieldURIOrConstant = new FieldURIOrConstantType();
		$sender->FieldURIOrConstant->Constant = new ConstantValueType();
		$sender->FieldURIOrConstant->Constant->Value = $this->sender;

		// Build the start date restriction.
		$greater_than = new IsGreaterThanOrEqualToType();
		$greater_than->FieldURI = new PathToUnindexedFieldType();
		$greater_than->FieldURI->FieldURI = UnindexedFieldURIType::ITEM_DATE_TIME_RECEIVED;
		$greater_than->FieldURIOrConstant = new FieldURIOrConstantType();
		$greater_than->FieldURIOrConstant->Constant = new ConstantValueType();
		$greater_than->FieldURIOrConstant->Constant->Value = $start_date->format('c');


		// Build the restriction.
		$request->Restriction = new RestrictionType();
		$request->Restriction->And = new AndType();
		$request->Restriction->And->IsGreaterThanOrEqualTo = $greater_than;
		$request->Restriction->And->Contains = $subject;
		$request->Restriction->And->IsEqualTo = $sender;


		// Return all message properties.
		$request->ItemShape = new ItemResponseShapeType();
		$request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;

		$request->QueryString = new QueryStringType();

		// Search in the user's inbox.
		$folder_id = new DistinguishedFolderIdType();
		$folder_id->Id = DistinguishedFolderIdNameType::INBOX;
		$request->ParentFolderIds->DistinguishedFolderId[] = $folder_id;

		$response = $client->FindItem($request);

		// Iterate over the results, printing any error messages or message subjects.
		$response_messages = $response->ResponseMessages->FindItemResponseMessage;
		foreach ($response_messages as $response_message) {
			// Make sure the request succeeded.
			if ($response_message->ResponseClass !== ResponseClassType::SUCCESS) {
				$code = $response_message->ResponseCode;
				$message = $response_message->MessageText;
				fwrite(
					STDERR,
					"Failed to search for messages with \"$code: $message\"\n"
				);
				continue;
			}

			// Iterate over the messages that were found, printing the subject for each.
			$items = $response_message->RootFolder->Items->Message;
			foreach ($items as $item) {
				$subject = $item->Subject;
				$id = $item->ItemId->Id;
				fwrite(STDOUT, "$subject: $id\n");

				// @TODO: get the item, because we need its content
			}
		}

		return 0;
	}
}
