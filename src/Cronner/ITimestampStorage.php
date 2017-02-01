<?php

declare(strict_types=1);

namespace stekycz\Cronner;

use DateTime;

interface ITimestampStorage
{

	/**
	 * Sets name of current task.
	 *
	 * @param string|null $taskName
	 */
	public function setTaskName(string $taskName = NULL);

	/**
	 * Saves current date and time as last invocation time.
	 *
	 * @param DateTime $now
	 */
	public function saveRunTime(DateTime $now);

	/**
	 * Returns date and time of last cron task invocation.
	 *
	 * @return DateTime|null
	 */
	public function loadLastRunTime();

}
