<?php declare (strict_types=1);

namespace JanMikes\Slacker;

use JanMikes\Slacker\ExchangeWebService\MailClient;
use Nette\Utils\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CheckMailCommand extends Command
{
	/**
	 * @var MailClient
	 */
	private $mailClient;


	public function __construct(MailClient $mailClient)
	{
		parent::__construct();

		$this->mailClient = $mailClient;
	}


	protected function configure(): void
	{
		$this->setName('check-mail');
	}


	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$messages = $this->mailClient->findMessagesIds();
		$bodies = $this->mailClient->getBodies($messages);

		foreach ($bodies as $body) {
			$match = Strings::match($body, '/<a tabindex=\"1\" href=\"(?<url>\S+)\"/');

			$output->writeln($match['url']);
		}

		// @TODO after clicking update all messages, mark as read

		return 0;
	}
}
