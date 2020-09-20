<?php

declare(strict_types=1);

namespace Bileto\Cronner\TimestampStorage;

use DateTimeInterface;
use Bileto\Cronner\ITimestampStorage;
use Nette\SmartObject;

class DummyStorage implements ITimestampStorage
{
    use SmartObject;

    /**
     * Sets name of current task.
     *
     * @param string|null $taskName
     */
    public function setTaskName(string $taskName = null): void
    {
        // Dummy
    }

    /**
     * Saves current date and time as last invocation time.
     *
     * @param DateTimeInterface $now
     */
    public function saveRunTime(DateTimeInterface $now): void
    {
        // Dummy
    }

    /**
     * Returns date and time of last cron task invocation.
     *
     * @return DateTimeInterface|null
     */
    public function loadLastRunTime(): ?DateTimeInterface
    {
        return null; // Dummy
    }

}
