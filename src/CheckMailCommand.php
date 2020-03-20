<?php declare (strict_types=1);

namespace JanMikes\Slacker;

use JanMikes\Slacker\ExchangeWebService\Exceptions\ExchangeWebServiceException;
use JanMikes\Slacker\ExchangeWebService\MailClient;
use Nette\Utils\Strings;
use Psr\Log\LoggerInterface;
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

	/**
	 * @var LoggerInterface
	 */
	private $logger;


	public function __construct(
		MailClient $mailClient,
		UrlExtractor $urlExtractor,
		HttpClient $httpClient,
		LoggerInterface $logger
	)
	{
		parent::__construct();

		$this->mailClient = $mailClient;
		$this->urlExtractor = $urlExtractor;
		$this->httpClient = $httpClient;
		$this->logger = $logger;
	}


	protected function configure(): void
	{
		$this->setName('check-mail');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$alreadyProcessedMessages = [];

		while(true) {
			$this->logger->info('Starting check run');

			try {
				$messages = $this->mailClient->findUnreadMessages();

				foreach ($messages as $key => $messageId) {
					if (in_array($messageId, $alreadyProcessedMessages, TRUE)) {
						unset($messages[$key]);
						$this->logger->notice(sprintf('Message already processed, skipping: %s', $messageId));

						continue;
					}
				}

				if (empty($messages)) {
					$this->logger->info('No new messages found');
				}

				$bodies = $this->mailClient->getMessages($messages);

				foreach ($bodies as $messageId => $message) {
					$this->logger->info(sprintf('Started processing message: %s', $messageId));
					$url = $this->urlExtractor->extract($message->Body->_);

					$this->logger->info(sprintf('Sending authorized request to %s', $url));
					$response = $this->httpClient->click($url);
					$responseBody = $response->getBody()->getContents();

					$this->logger->info(sprintf('Response: %s', Strings::truncate($responseBody, 200)));

					$this->mailClient->markMessageAsRead($message);
					$this->logger->info('Marked message as read');

					$alreadyProcessedMessages[] = $messageId;
					$this->logger->info('Processing message finished');
				}

			} catch (ExchangeWebServiceException $exception) {
				$this->logger->error($exception->getMessage());
			} catch (\Throwable $exception) {
				$this->logger->critical($exception->getMessage());

				throw $exception;
			}

			$nextCheckMinutes = random_int(1, 9);
			$this->logger->info("Next check in $nextCheckMinutes minutes");
			sleep(60 * $nextCheckMinutes);
		}
	}
}
