<?php declare (strict_types=1);

namespace JanMikes\Slacker\Utils;

use Nette\Utils\Strings;

final class StringsExtractor
{
	public function extractUrl(string $mailBody): string
	{
		$match = Strings::match($mailBody, '/<a tabindex=\"1\" href=\"(?<url>\S+)\"/');

		if (!isset($match['url'])) {
			throw new \InvalidArgumentException('Content does not contain clickable link');
		}

		return $match['url'];
	}

	public function extractReportText(string $content): string
	{
		$match = Strings::match($content, '/(?<text>Ověření([\w ]*))/u');

		if (!isset($match['text'])) {
			return 'Unknown';
		}

		return $match['text'];
	}
}
