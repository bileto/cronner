<?php

namespace stekycz\Cronner\TimestampStorage;

use Nette\Object;
use LogicException;
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
	private $path;

	/**
	 * @param string $path
	 */
	public function __construct($path) {
		$this->path = $path;
	}

	/**
	 * Saves current date and time as last invocation time.
	 *
	 * @param \DateTime $now
	 */
	public function saveRunTime(DateTime $now) {
		// TODO - save current date & time
		throw new LogicException("Not implemented yet.");
	}

	/**
	 * Returns date and time of last cron task invocation.
	 *
	 * @return \DateTime|null
	 */
	public function loadLastRunTime() {
		// TODO - load date & time of last invocation
		throw new LogicException("Not implemented yet.");
		return null;
	}

}
