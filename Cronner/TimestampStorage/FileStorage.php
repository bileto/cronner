<?php

namespace stekycz\Cronner\TimestampStorage;

use DateTime;
use Nette;
use Nette\Object;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use stekycz\Cronner\EmptyTaskNameException;
use stekycz\Cronner\InvalidTaskNameException;
use stekycz\Cronner\ITimestampStorage;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class FileStorage extends Object implements ITimestampStorage
{

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
	public function __construct($directory)
	{
		Nette\Utils\SafeStream::register();
		$directory = rtrim($directory, DIRECTORY_SEPARATOR);
		FileSystem::createDir($directory);
		$this->directory = $directory;
	}



	/**
	 * Sets name of current task.
	 *
	 * @param string|null $taskName
	 */
	public function setTaskName($taskName = NULL)
	{
		if ($taskName !== NULL && (!$taskName || !is_string($taskName) || Strings::length($taskName) <= 0)) {
			throw new InvalidTaskNameException('Given task name is not valid.');
		}
		$this->taskName = $taskName;
	}



	/**
	 * Saves current date and time as last invocation time.
	 *
	 * @param \DateTime $now
	 */
	public function saveRunTime(DateTime $now)
	{
		$filepath = $this->buildFilePath();
		file_put_contents($filepath, $now->format(self::DATETIME_FORMAT));
	}



	/**
	 * Returns date and time of last cron task invocation.
	 *
	 * @return \DateTime|null
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
	 *
	 * @return string
	 */
	private function buildFilePath()
	{
		if ($this->taskName === NULL) {
			throw new EmptyTaskNameException('Task name was not set.');
		}

		return 'safe://' . $this->directory . '/' . sha1($this->taskName);
	}

}
