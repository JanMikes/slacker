<?php declare (strict_types=1);

namespace JanMikes\Slacker\ExchangeWebService;

use jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use jamesiarmes\PhpEws\Type\AndType;
use jamesiarmes\PhpEws\Type\ConstantValueType;
use jamesiarmes\PhpEws\Type\ContainsExpressionType;
use jamesiarmes\PhpEws\Type\FieldURIOrConstantType;
use jamesiarmes\PhpEws\Type\IsEqualToType;
use jamesiarmes\PhpEws\Type\IsNotEqualToType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use jamesiarmes\PhpEws\Type\RestrictionType;

final class RestrictionsFactory
{
	public function createRestrictions(string $subject, string $sender): RestrictionType
	{
		$subjectRestriction = $this->createSubjectRestriction($subject);
		$senderRestriction = $this->createSenderRestriction($sender);
		$unreadRestriction = $this->createUnreadRestriction();

		$restriction = new RestrictionType();
		$restriction->And = new AndType();
		$restriction->And->IsNotEqualTo = $unreadRestriction;
		$restriction->And->Contains = $subjectRestriction;
		$restriction->And->IsEqualTo = $senderRestriction;

		return $restriction;
	}


	private function createSubjectRestriction(string $subject): ContainsExpressionType
	{
		$restriction = new ContainsExpressionType();
		$restriction->FieldURI = new PathToUnindexedFieldType();
		$restriction->FieldURI->FieldURI = UnindexedFieldURIType::ITEM_SUBJECT;
		$restriction->Constant = new ConstantValueType();
		$restriction->Constant->Value = $subject;

		return $restriction;
	}


	private function createSenderRestriction(string $sender): IsEqualToType
	{
		$restriction = new IsEqualToType();
		$restriction->FieldURI = new PathToUnindexedFieldType();
		$restriction->FieldURI->FieldURI = UnindexedFieldURIType::MESSAGE_SENDER;
		$restriction->FieldURIOrConstant = new FieldURIOrConstantType();
		$restriction->FieldURIOrConstant->Constant = new ConstantValueType();
		$restriction->FieldURIOrConstant->Constant->Value = $sender;

		return $restriction;
	}


	private function createUnreadRestriction(): IsNotEqualToType
	{
		$restriction = new IsNotEqualToType();
		$restriction->FieldURI = new PathToUnindexedFieldType();
		$restriction->FieldURI->FieldURI = UnindexedFieldURIType::MESSAGE_IS_READ;
		$restriction->FieldURIOrConstant = new FieldURIOrConstantType();
		$restriction->FieldURIOrConstant->Constant = new ConstantValueType();
		$restriction->FieldURIOrConstant->Constant->Value = 'true';

		return $restriction;
	}
}
