<?php

namespace stekycz\Cronner\TimestampStorage;

use Nette;
use stekycz\Cronner\EmptyTaskNameException;
use Nette\Utils\Strings;
use stekycz\Cronner\InvalidTaskNameException;
use Nette\Object;
use stekycz\Cronner\FileCannotBeClosedException;
use stekycz\Cronner\FileCannotBeOpenedException;
use stekycz\Cronner\DirectoryNotFoundException;
use DateTime;
use stekycz\Cronner\ITimestampStorage;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-04
 */
class FileStorage extends Object implements ITimestampStorage {

	/**
	 * @var string
	 */
	private $directory;

	/**
	 * @var string
	 */
	private $taskName = null;

	/**
	 * @param string $directory
	 */
	public function __construct($directory) {
		$this->directory = $directory;
	}

	/**
	 * Sets name of current task.
	 *
	 * @param string|null $taskName
	 */
	public function setTaskName($taskName = null) {
		if ($taskName !== null
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
	public function saveRunTime(DateTime $now) {
		$this->checkDirectoryExists();
		$fileHandle = $this->openFile();
		fwrite($fileHandle, $now);
		$this->closeFile($fileHandle);
	}

	/**
	 * Returns date and time of last cron task invocation.
	 *
	 * @return \DateTime|null
	 */
	public function loadLastRunTime() {
		$this->checkDirectoryExists();
		$date = null;
		$filepath = $this->buildFilePath();
		if (file_exists($filepath)) {
			$fileHandle = $this->openFile(true);
			$size = filesize($filepath);
			$date = fread($fileHandle, $size);
			$this->closeFile($fileHandle);
			$date = new Nette\DateTime($date);
		}
		return $date;
	}

	/**
	 * Checks if directory exist.
	 */
	private function checkDirectoryExists() {
		if (!is_dir($this->directory)) {
			throw new DirectoryNotFoundException();
		}
	}

	/**
	 * Builds file path from directory and task name.
	 *
	 * @return string
	 */
	private function buildFilePath() {
		if ($this->taskName === null) {
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
	private function openFile($read = false) {
		$fileHandle = fopen($this->buildFilePath(), $read ? 'rb' : 'w+b');
		if ($fileHandle === false) {
			throw new FileCannotBeOpenedException();
		}
		return $fileHandle;
	}

	/**
	 * Closes file which is opened by given handle.
	 *
	 * @param resource $fileHandle
	 */
	private function closeFile($fileHandle) {
		if (fclose($fileHandle) === false) {
			throw new FileCannotBeClosedException();
		}
	}

}
