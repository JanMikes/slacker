<?php declare (strict_types=1);

namespace JanMikes\Slacker\ExchangeWebService;

use jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use jamesiarmes\PhpEws\Type\ConstantValueType;
use jamesiarmes\PhpEws\Type\FieldURIOrConstantType;
use jamesiarmes\PhpEws\Type\IsEqualToType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
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
}
