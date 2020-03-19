<?php declare (strict_types=1);

namespace JanMikes\Slacker;

use Nette\Utils\Strings;

final class UrlExtractor
{
	public function extract(string $mailBody): string
	{
		$match = Strings::match($mailBody, '/<a tabindex=\"1\" href=\"(?<url>\S+)\"/');

		if (!isset($match['url'])) {
			throw new \InvalidArgumentException('Content does not contain clickable link');
		}

		return $match['url'];
	}
}
