<?php declare (strict_types=1);

namespace JanMikes\Slacker;

use JanMikes\Slacker\ExchangeWebService\MailClient;
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
		$messages = $this->mailClient->getMessagesIds();

		foreach ($messages as $messageId) {
			$output->writeln($messageId);
		}

		return 0;
	}
}
