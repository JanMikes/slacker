<?php declare (strict_types=1);

namespace JanMikes\Slacker;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

final class HttpClient
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
	 * @var Client|null
	 */
	private $client;


	public function __construct(string $user, string $password)
	{
		$this->user = $user;
		$this->password = $password;
	}


	public function click(string $url): ResponseInterface
	{
		$client = $this->getClient();

		return $client->get($url);
	}


	private function getClient(): Client
	{
		if (!$this->client) {
			$this->client = new Client([
				'auth' => [$this->user, $this->password],
			]);
		}

		return $this->client;
	}
}