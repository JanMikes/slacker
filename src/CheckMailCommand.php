<?php declare (strict_types=1);

namespace JanMikes\Slacker;

use JanMikes\Slacker\ExchangeWebService\Exceptions\ExchangeWebServiceException;
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

	/**
	 * @var UrlExtractor
	 */
	private $urlExtractor;

	/**
	 * @var HttpClient
	 */
	private $httpClient;


	public function __construct(MailClient $mailClient, UrlExtractor $urlExtractor, HttpClient $httpClient)
	{
		parent::__construct();

		$this->mailClient = $mailClient;
		$this->urlExtractor = $urlExtractor;
		$this->httpClient = $httpClient;
	}


	protected function configure(): void
	{
		$this->setName('check-mail');
	}


	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$alreadyProcessedMessages = [];

		while(true) {
			$output->writeln(sprintf('Starting check (%s)', date('H:i:s')));

			try {
				$messages = $this->mailClient->findMessagesIds();

				foreach ($messages as $key => $messageId) {
					if (in_array($messageId, $alreadyProcessedMessages, TRUE)) {
						unset($messages[$messageId]);
						$output->write(sprintf('Message already processed, skipping: %s', $messageId));

						continue;
					}
				}

				$bodies = $this->mailClient->getBodies($messages);

				foreach ($bodies as $messageId => $body) {
					$output->write(sprintf('Started processing message: %s', $messageId));
					$url = $this->urlExtractor->extract($body);

					$output->writeln(sprintf('Sending authorized request to %s', $url));

					$response = $this->httpClient->click($url);
					$responseBody = $response->getBody()->getContents();

					$output->writeln(sprintf('Response: %s', $responseBody));

					$this->mailClient->markMessageAsRead($messageId);
					$output->writeln('Marked message as read');

					$alreadyProcessedMessages[] = $messageId;
					$output->writeln('Processing message finished');
					$output->writeln('---');
				}

			} catch (ExchangeWebServiceException $exception) {
				$output->writeln($exception->getMessage());
			} finally {
				$nextCheckMinutes = random_int(1, 9);
				$output->writeln("Next check in $nextCheckMinutes minutes");
				sleep(5);
				// sleep(60 * $nextCheckMinutes);
			}
		}

		return 0;
	}
}
