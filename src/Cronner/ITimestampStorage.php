<?php

declare(strict_types=1);

namespace Bileto\Cronner;

use DateTimeInterface;

interface ITimestampStorage
{

	/**
	 * Sets name of current task.
	 */
	public function setTaskName(?string $taskName = null): void;

	/**
	 * Saves current date and time as last invocation time.
	 */
	public function saveRunTime(DateTimeInterface $runTime): void;

	/**
	 * Returns date and time of last cron task invocation.
	 */
	public function loadLastRunTime(): ?DateTimeInterface;
}
