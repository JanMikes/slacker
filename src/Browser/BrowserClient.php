<?php declare (strict_types=1);

namespace JanMikes\Slacker\Browser;

use GuzzleHttp\Psr7\Uri;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use Psr\Log\LoggerInterface;

final class BrowserClient
{
	/**
	 * @var string
	 */
	private $user;

	/**
	 * @var string
	 */
	private $password;

	/**
	 * @var LoggerInterface
	 */
	private $logger;


	public function __construct(string $user, string $password, LoggerInterface $logger)
	{
		$this->user = $user;
		$this->password = $password;
		$this->logger = $logger;
	}


	public function click(string $url): string
	{
		$browserFactory = new BrowserFactory('chromium');

		// starts headless chrome
		$browser = $browserFactory->createBrowser([
			'ignoreCertificateErrors' => true,
			'debugLogger' => $this->logger,
			'noSandbox' => true,
		]);

		$uri = (new Uri($url))->withUserInfo($this->user, $this->password);

		// creates a new page and navigate to an url
		$page = $browser->createPage();
		$navigation = $page->navigate((string) $uri);
		$navigation->waitForNavigation(Page::NETWORK_IDLE, 10000);

		// evaluate script in the browser
		$evaluation = $page->evaluate('document.documentElement.innerHTML');

		// wait for the value to return and get it
		$content = $evaluation->getReturnValue();

		// bye
		$browser->close();

		return $content;
	}
}
