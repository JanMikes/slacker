<?php declare (strict_types=1);

namespace JanMikes\Slacker\Tests;

use JanMikes\Slacker\UrlExtractor;
use PHPUnit\Framework\TestCase;

class UrlExtractorTest extends TestCase
{
	/**
	 * @dataProvider provideExtractData
	 */
	public function testExtract(string $content): void
	{
		$extractor = new UrlExtractor();
		$url = $extractor->extract($content);

		$this->assertSame('https://www.google.com', $url);
	}


	public function provideExtractData(): \Generator
	{
		yield [file_get_contents(__DIR__ . '/mail.html')];
	}
}
