<?php declare (strict_types=1);

namespace JanMikes\Slacker\Tests\Utils;

use JanMikes\Slacker\Utils\StringsExtractor;
use PHPUnit\Framework\TestCase;

class StringsExtractorTest extends TestCase
{
	/**
	 * @dataProvider provideExtractUrlData
	 */
	public function testExtractUrl(string $content): void
	{
		$extractor = new StringsExtractor();
		$url = $extractor->extractUrl($content);

		$this->assertSame('https://www.google.com', $url);
	}


	/**
	 * @return \Generator<mixed>
	 */
	public function provideExtractUrlData(): \Generator
	{
		yield [file_get_contents(__DIR__ . '/mail.html')];
	}


	/**
	 * @dataProvider provideReportTextData
	 */
	public function testExtractReportText(string $content): void
	{
		$extractor = new StringsExtractor();
		$result = $extractor->extractReportText($content);

		$this->assertSame('Ověření nebylo úspěšné', $result);
	}


	/**
	 * @return \Generator<mixed>
	 */
	public function provideReportTextData(): \Generator
	{
		yield [file_get_contents(__DIR__ . '/response.html')];
		yield [file_get_contents(__DIR__ . '/response-whitespaces.html')];
	}
}
