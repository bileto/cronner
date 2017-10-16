<?php

declare(strict_types=1);

namespace stekycz\Cronner\TimestampStorage;

use DateTime;
use Nette\Utils\FileSystem;
use Nette\Utils\SafeStream;
use Nette\Utils\Strings;
use stekycz\Cronner\Exceptions\EmptyTaskNameException;
use stekycz\Cronner\Exceptions\InvalidTaskNameException;
use stekycz\Cronner\ITimestampStorage;

class FileStorage implements ITimestampStorage
{
	use \Nette\SmartObject;

	const DATETIME_FORMAT = 'Y-m-d H:i:s O';

	/**
	 * @var string
	 */
	private $directory;

	/**
	 * @var string|NULL
	 */
	private $taskName = NULL;

	/**
	 * @param string $directory
	 */
	public function __construct(string $directory)
	{
		SafeStream::register();
		$directory = rtrim($directory, DIRECTORY_SEPARATOR);
		FileSystem::createDir($directory);
		$this->directory = $directory;
	}

	/**
	 * Sets name of current task.
	 *
	 * @param string|null $taskName
	 */
	public function setTaskName(string $taskName = NULL)
	{
		if ($taskName !== NULL && Strings::length($taskName) <= 0) {
			throw new InvalidTaskNameException('Given task name is not valid.');
		}
		$this->taskName = $taskName;
	}

	/**
	 * Saves current date and time as last invocation time.
	 *
	 * @param DateTime $now
	 */
	public function saveRunTime(DateTime $now)
	{
		$filepath = $this->buildFilePath();
		file_put_contents($filepath, $now->format(self::DATETIME_FORMAT));
	}

	/**
	 * Returns date and time of last cron task invocation.
	 *
	 * @return DateTime|null
	 */
	public function loadLastRunTime()
	{
		$date = NULL;
		$filepath = $this->buildFilePath();
		if (file_exists($filepath)) {
			$date = file_get_contents($filepath);
			$date = DateTime::createFromFormat(self::DATETIME_FORMAT, $date);
		}

		return $date ? $date : NULL;
	}

	/**
	 * Builds file path from directory and task name.
	 */
	private function buildFilePath() : string
	{
		if ($this->taskName === NULL) {
			throw new EmptyTaskNameException('Task name was not set.');
		}

		return SafeStream::PROTOCOL . '://' . $this->directory . '/' . sha1($this->taskName);
	}

}
