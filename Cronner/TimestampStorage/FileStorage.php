<?php

namespace stekycz\Cronner\TimestampStorage;

use Nette;
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
	private $filepath;

	/**
	 * @param string $filepath
	 */
	public function __construct($filepath) {
		$this->filepath = $filepath;
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
		if (file_exists($this->filepath)) {
			$fileHandle = $this->openFile(true);
			$size = filesize($this->filepath);
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
		$dirpath = dirname($this->filepath);
		if (!is_dir($dirpath)) {
			throw new DirectoryNotFoundException();
		}
	}

	/**
	 * Opens file.
	 *
	 * @param bool $read
	 * @return resource
	 */
	private function openFile($read = false) {
		$fileHandle = fopen($this->filepath, $read ? 'rb' : 'w+b');
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
