<?php

declare(strict_types=1);

namespace stekycz\Cronner\TimestampStorage;

use DateTimeInterface;
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
	 * @param DateTimeInterface $now
	 */
	public function saveRunTime(DateTimeInterface $now)
	{
		// Dummy
	}

	/**
	 * Returns date and time of last cron task invocation.
	 *
	 * @return DateTimeInterface|null
	 */
	public function loadLastRunTime()
	{
		return NULL; // Dummy
	}

}
