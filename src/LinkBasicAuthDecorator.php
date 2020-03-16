<?php declare (strict_types=1);

namespace JanMikes\Slacker;

final class LinkBasicAuthDecorator
{
	/**
	 * @var string
	 */
	private $username;

	/**
	 * @var string
	 */
	private $password;


	public function __construct(string $username, string $password)
	{
		$this->username = $username;
		$this->password = $password;
	}


	public function decorate(string $url): string
	{
		return $url;
	}
}
