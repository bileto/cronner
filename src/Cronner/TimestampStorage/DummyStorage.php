<?php

declare(strict_types=1);

namespace stekycz\Cronner\TimestampStorage;

use DateTime;
use stekycz\Cronner\ITimestampStorage;

class DummyStorage implements ITimestampStorage
{
	use \Nette\SmartObject;

	/**
	 * Sets name of current task.
	 *
	 * @param string|null $taskName
	 */
	public function setTaskName(string $taskName = NULL)
	{
		// Dummy
	}

	/**
	 * Saves current date and time as last invocation time.
	 *
	 * @param DateTime $now
	 */
	public function saveRunTime(DateTime $now)
	{
		// Dummy
	}

	/**
	 * Returns date and time of last cron task invocation.
	 *
	 * @return DateTime|null
	 */
	public function loadLastRunTime()
	{
		return NULL; // Dummy
	}

}
