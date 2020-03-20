<?php declare (strict_types=1);

namespace JanMikes\Slacker\ExchangeWebService;

use jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use jamesiarmes\PhpEws\Type\AndType;
use jamesiarmes\PhpEws\Type\ConstantValueType;
use jamesiarmes\PhpEws\Type\FieldURIOrConstantType;
use jamesiarmes\PhpEws\Type\IsEqualToType;
use jamesiarmes\PhpEws\Type\MultipleOperandBooleanExpressionType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use jamesiarmes\PhpEws\Type\RestrictionType;
use jamesiarmes\PhpEws\Type\SearchExpressionType;

final class RestrictionsFactory
{
	public function createSubjectRestriction(string $subject): SearchExpressionType
	{
		$restriction = new IsEqualToType();
		$restriction->FieldURI = new PathToUnindexedFieldType();
		$restriction->FieldURI->FieldURI = UnindexedFieldURIType::ITEM_SUBJECT;
		$restriction->FieldURIOrConstant = new FieldURIOrConstantType();
		$restriction->FieldURIOrConstant->Constant = new ConstantValueType();
		$restriction->FieldURIOrConstant->Constant->Value = $subject;

		return $restriction;
	}


	public function createSenderRestriction(string $sender): SearchExpressionType
	{
		$restriction = new IsEqualToType();
		$restriction->FieldURI = new PathToUnindexedFieldType();
		$restriction->FieldURI->FieldURI = UnindexedFieldURIType::MESSAGE_SENDER;
		$restriction->FieldURIOrConstant = new FieldURIOrConstantType();
		$restriction->FieldURIOrConstant->Constant = new ConstantValueType();
		$restriction->FieldURIOrConstant->Constant->Value = $sender;

		return $restriction;
	}


	public function createUnreadRestriction(): SearchExpressionType
	{
		$restriction = new IsEqualToType();
		$restriction->FieldURI = new PathToUnindexedFieldType();
		$restriction->FieldURI->FieldURI = UnindexedFieldURIType::MESSAGE_IS_READ;
		$restriction->FieldURIOrConstant = new FieldURIOrConstantType();
		$restriction->FieldURIOrConstant->Constant = new ConstantValueType();
		$restriction->FieldURIOrConstant->Constant->Value = false;

		return $restriction;
	}


	/**
	 * @param SearchExpressionType[]
	 */
	public function createRestrictions(array $restrictions): RestrictionType
	{
		$groupRestriction = new RestrictionType();
		$tempGroupRestriction = $groupRestriction;

		foreach ($restrictions as $restriction) {
			if (isset($tempGroupRestriction->IsEqualTo)) {
				$tempGroupRestriction->And = new AndType();
				$tempGroupRestriction = $tempGroupRestriction->And;
			}

			$tempGroupRestriction->IsEqualTo = $restriction;
		}

		return $groupRestriction;
	}
}
