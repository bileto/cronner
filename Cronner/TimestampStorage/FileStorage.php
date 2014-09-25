<?php

namespace stekycz\Cronner\TimestampStorage;

use DateTime;
use Nette;
use Nette\Object;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use stekycz\Cronner\EmptyTaskNameException;
use stekycz\Cronner\FileCannotBeClosedException;
use stekycz\Cronner\FileCannotBeOpenedException;
use stekycz\Cronner\InvalidTaskNameException;
use stekycz\Cronner\ITimestampStorage;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class FileStorage extends Object implements ITimestampStorage
{

	/**
	 * @var string
	 */
	private $directory;

	/**
	 * @var string
	 */
	private $taskName = NULL;



	/**
	 * @param string $directory
	 */
	public function __construct($directory)
	{
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
		if ($taskName !== NULL
			&& (!$taskName || !is_string($taskName) || Strings::length($taskName) <= 0)
		) {
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
		$fileHandle = $this->openFile();
		fwrite($fileHandle, $now);
		$this->closeFile($fileHandle);
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
			$fileHandle = $this->openFile(TRUE);
			$size = filesize($filepath);
			$date = fread($fileHandle, $size);
			$this->closeFile($fileHandle);
			$date = new Nette\Utils\DateTime($date);
		}

		return $date;
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

		return $this->directory . '/' . sha1($this->taskName);
	}



	/**
	 * Opens file.
	 *
	 * @param bool $read
	 * @return resource
	 */
	private function openFile($read = FALSE)
	{
		$fileHandle = fopen($this->buildFilePath(), $read ? 'rb' : 'w+b');
		if ($fileHandle === FALSE) {
			throw new FileCannotBeOpenedException();
		}

		return $fileHandle;
	}



	/**
	 * Closes file which is opened by given handle.
	 *
	 * @param resource $fileHandle
	 */
	private function closeFile($fileHandle)
	{
		if (fclose($fileHandle) === FALSE) {
			throw new FileCannotBeClosedException();
		}
	}

}
