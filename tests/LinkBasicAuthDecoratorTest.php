<?php declare (strict_types=1);

namespace JanMikes\Slacker\Tests;

use JanMikes\Slacker\LinkBasicAuthDecorator;
use PHPUnit\Framework\TestCase;

class LinkBasicAuthDecoratorTest extends TestCase
{
	public function testDecorate(): void
	{
		$linkDecorator = new LinkBasicAuthDecorator('user', 'pass');
		$decoratedLink = $linkDecorator->decorate('https://google.com');

		$this->assertSame('https://user:pass@google.com', $decoratedLink);
	}
}
