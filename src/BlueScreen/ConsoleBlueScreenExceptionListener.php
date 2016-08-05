<?php

namespace VasekPurchart\TracyBlueScreenBundle\BlueScreen;

use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Tracy\BlueScreen;
use Tracy\Logger as TracyLogger;

class ConsoleBlueScreenExceptionListener
{

	/** @var \Tracy\Logger */
	private $tracyLogger;

	/** @var \Tracy\BlueScreen */
	private $blueScreen;

	/** @var string|null */
	private $logDirectory;

	/** @var string|null */
	private $browser;

	/**
	 * @param \Tracy\Logger $tracyLogger
	 * @param \Tracy\BlueScreen $blueScreen
	 * @param string|null $logDirectory
	 * @param string|null $browser
	 */
	public function __construct(
		TracyLogger $tracyLogger,
		BlueScreen $blueScreen,
		$logDirectory,
		$browser
	)
	{
		$this->tracyLogger = $tracyLogger;
		$this->blueScreen = $blueScreen;
		$this->logDirectory = $logDirectory;
		$this->browser = $browser;
	}

	public function onConsoleException(ConsoleExceptionEvent $event)
	{
		if ($this->tracyLogger->directory === null) {
			$this->tracyLogger->directory = $this->logDirectory;
		}

		if (
			$this->tracyLogger->directory === null
			|| !is_dir($this->tracyLogger->directory)
			|| !is_writable($this->tracyLogger->directory)
		) {
			throw new \InvalidArgumentException(sprintf(
				'Log directory must be a writable directory, %s [%s] given',
				$this->tracyLogger->directory,
				gettype($this->tracyLogger->directory)
			), 0, $event->getException());
		}

		$exception = $event->getException();
		$exceptionFile = $this->tracyLogger->getExceptionFile($exception);
		$this->blueScreen->renderToFile($exception, $exceptionFile);

		$output = $event->getOutput();
		$this->printErrorMessage($output, sprintf('BlueScreen saved in file: %s', $exceptionFile));
		if ($this->browser === null) {
			return;
		}
		// @codeCoverageIgnoreStart
		// uses global state
		$this->openBrowser($this->browser, $exceptionFile);
	}
	// @codeCoverageIgnoreEnd

	/**
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @param string $message
	 */
	private function printErrorMessage(OutputInterface $output, $message)
	{
		$message = sprintf('<error>%s</error>', $message);
		if ($output instanceof ConsoleOutputInterface) {
			$output->getErrorOutput()->writeln($message);
		} else {
			$output->writeln($message);
		}
	}

	/**
	 * @codeCoverageIgnore uses global state
	 *
	 * @param string $browser
	 * @param string $file
	 */
	private function openBrowser($browser, $file)
	{
		static $showed = false;
		if ($showed) {
			return;
		}

		exec(sprintf('%s %s', $browser, escapeshellarg($file)));
		$showed = true;
	}

}
