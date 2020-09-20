<?php

declare(strict_types=1);

namespace Bileto\Cronner;

use DateTimeInterface;

interface ITimestampStorage
{

    /**
     * Sets name of current task.
     *
     * @param string|null $taskName
     */
    public function setTaskName(string $taskName = NULL): void;

    /**
     * Saves current date and time as last invocation time.
     *
     * @param DateTimeInterface $now
     */
    public function saveRunTime(DateTimeInterface $now): void;

    /**
     * Returns date and time of last cron task invocation.
     *
     * @return DateTimeInterface|null
     */
    public function loadLastRunTime(): ?DateTimeInterface;

}
