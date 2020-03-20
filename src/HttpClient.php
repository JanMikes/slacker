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


	public function __construct(string $user, string $password)
	{
		$this->user = $user;
		$this->password = $password;
	}


	public function click(string $url): ResponseInterface
	{
		$client = new Client();

		return $client->get(html_entity_decode($url), [
			'verify' => false,
			'auth' => [$this->user, $this->password, 'ntlm'],
		]);
	}
}
