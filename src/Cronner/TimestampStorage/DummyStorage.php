<?php

declare(strict_types=1);

namespace Bileto\Cronner\TimestampStorage;

use DateTimeInterface;
use Nette\SmartObject;
use Bileto\Cronner\ITimestampStorage;

final class DummyStorage implements ITimestampStorage
{
	use SmartObject;

	/** @var string|null */
	private $taskName;
	/** @var DateTimeInterface|null */
	private $runTime;

	/**
	 * Sets name of current task.
	 */
	public function setTaskName(?string $taskName = null): void
	{
		$this->taskName = $taskName;
	}

	/**
	 * Saves current date and time as last invocation time.
	 */
	public function saveRunTime(DateTimeInterface $runTime): void
	{
		$this->runTime = $runTime;
	}

	/**
	 * Returns date and time of last cron task invocation.
	 */
	public function loadLastRunTime(): ?DateTimeInterface
	{
		return $this->runTime;
	}
}
