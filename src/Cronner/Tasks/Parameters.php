<?php

declare(strict_types=1);

namespace Bileto\Cronner\Tasks;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Nette\Reflection\Method;
use Nette\SmartObject;
use Nette\Utils\Strings;
use Nette\Utils\DateTime as NetteDateTime;
use Bileto\Cronner\Exceptions\InvalidArgumentException;

final class Parameters
{
    use SmartObject;

    const TASK = 'cronner-task';
    const PERIOD = 'cronner-period';
    const DAYS = 'cronner-days';
    const DAYS_OF_MONTH = 'cronner-days-of-month';
    const TIME = 'cronner-time';

    /** @var array */
    private $values;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        $values[static::TASK] = isset($values[static::TASK]) && is_string($values[static::TASK])
            ? Strings::trim($values[static::TASK])
            : '';
        $this->values = $values;
    }

    public function getName(): string
    {
        return $this->values[static::TASK];
    }

    public function isTask(): bool
    {
        return Strings::length($this->values[static::TASK]) > 0;
    }

    /**
     * Returns true if today is allowed day of week.
     * @param DateTimeInterface $now
     * @return bool
     */
    public function isInDay(DateTimeInterface $now): bool
    {
        if (($days = $this->values[static::DAYS]) !== NULL) {
            return in_array($now->format('D'), $days);
        }

        return TRUE;
    }

    /**
     * Returns true if today is allowed day of month.
     * @param DateTimeInterface $now
     * @return bool
     */
    public function isInDayOfMonth(DateTimeInterface $now): bool
    {
        if (($days = $this->values[static::DAYS_OF_MONTH]) !== null) {
            return in_array($now->format('j'), $days);
        }

        return true;
    }

    /**
     * Returns true if current time is in allowed range.
     * @param DateTimeInterface $now
     * @return bool
     */
    public function isInTime(DateTimeInterface $now): bool
    {
        if ($times = $this->values[static::TIME]) {
            foreach ($times as $time) {
                if ($time['to'] && $time['to'] >= $now->format('H:i') && $time['from'] <= $now->format('H:i')) {
                    // Is in range with precision to minutes
                    return true;
                } elseif ($time['from'] == $now->format('H:i')) {
                    // Is in specific minute
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Returns true if current time is next period of invocation.
     * @param DateTimeInterface $now
     * @param DateTimeInterface|null $lastRunTime
     * @return bool
     */
    public function isNextPeriod(DateTimeInterface $now, DateTimeInterface $lastRunTime = null): bool
    {
        if (
            $lastRunTime !== null
            && !$lastRunTime instanceof DateTimeImmutable
            && !$lastRunTime instanceof DateTime
        ) {
            throw new InvalidArgumentException;
        }

        if (isset($this->values[static::PERIOD]) && $this->values[static::PERIOD]) {
            // Prevent run task on next cronner run because of a few seconds shift
            $now = NetteDateTime::from($now)->modifyClone('+5 seconds');

            return $lastRunTime === null || $lastRunTime->modify('+ ' . $this->values[static::PERIOD]) <= $now;
        }

        return TRUE;
    }

    /**
     * Parse cronner values from annotations.
     * @param Method $method
     * @param DateTime $now
     * @return array
     */
    public static function parseParameters(Method $method, DateTime $now): array
    {
        $taskName = null;
        if ($method->hasAnnotation(Parameters::TASK)) {
            $className = $method->getDeclaringClass()->getName();
            $methodName = $method->getName();
            $taskName = $className . ' - ' . $methodName;
        }

        $taskAnnotation = $method->getAnnotation(Parameters::TASK);

        $parameters = [
            static::TASK => is_string($taskAnnotation)
                ? Parser::parseName($taskAnnotation)
                : $taskName,
            static::PERIOD => $method->hasAnnotation(Parameters::PERIOD)
                ? Parser::parsePeriod((string)$method->getAnnotation(Parameters::PERIOD))
                : NULL,
            static::DAYS => $method->hasAnnotation(Parameters::DAYS)
                ? Parser::parseDays((string)$method->getAnnotation(Parameters::DAYS))
                : NULL,
            static::DAYS_OF_MONTH => $method->hasAnnotation(Parameters::DAYS_OF_MONTH)
                ? Parser::parseDaysOfMonth((string)$method->getAnnotation(Parameters::DAYS_OF_MONTH), $now)
                : NULL,
            static::TIME => $method->hasAnnotation(Parameters::TIME)
                ? Parser::parseTimes((string)$method->getAnnotation(Parameters::TIME))
                : NULL,
        ];

        return $parameters;
    }

}
