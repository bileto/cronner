<?php

declare(strict_types=1);

namespace Bileto\Cronner\Tasks;

use Bileto\Cronner\Utils\ReflectionSupport;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Nette;
use Nette\SmartObject;
use Nette\Utils\Strings;
use ReflectionMethod;

final class Parameters
{
	use SmartObject;

	public const TASK = 'cronner-task';

	public const PERIOD = 'cronner-period';

	public const DAYS = 'cronner-days';

	public const DAYS_OF_MONTH = 'cronner-days-of-month';

	public const TIME = 'cronner-time';

	/** @var array<mixed> */
	private $values;

	/**
	 * @param array<mixed> $values
	 */
	public function __construct(array $values = [])
	{
		$values[Parameters::TASK] = isset($values[Parameters::TASK]) && is_string($values[Parameters::TASK])
			? Strings::trim($values[Parameters::TASK])
			: '';

		$this->values = $values;
	}

	/**
	 * Parse cronner values from annotations.
	 *
	 * @return array<mixed>
	 */
	public static function parseParameters(ReflectionMethod $method, DateTimeInterface $now): array
	{
		$reflectionSupport = new ReflectionSupport();

		$taskName = null;

		if ($reflectionSupport->hasMethodAnnotation($method, Parameters::TASK)) {
			$className = $method->getDeclaringClass()->getName();
			$methodName = $method->getName();
			$taskName = $className . ' - ' . $methodName;
		}

		$taskAnnotation = $reflectionSupport->getMethodAnnotation($method, Parameters::TASK);

		$parameters = [
			Parameters::TASK => is_string($taskAnnotation)
				? Parser::parseName($taskAnnotation)
				: $taskName,
			Parameters::PERIOD => $reflectionSupport->hasMethodAnnotation($method, Parameters::PERIOD)
				? Parser::parsePeriod((string) $reflectionSupport->getMethodAnnotation($method,Parameters::PERIOD))
				: null,
			Parameters::DAYS => $reflectionSupport->hasMethodAnnotation($method, Parameters::DAYS)
				? Parser::parseDays((string) $reflectionSupport->getMethodAnnotation($method,Parameters::DAYS))
				: null,
			Parameters::DAYS_OF_MONTH => $reflectionSupport->hasMethodAnnotation($method, Parameters::DAYS_OF_MONTH)
				? Parser::parseDaysOfMonth((string) $reflectionSupport->getMethodAnnotation($method,Parameters::DAYS_OF_MONTH), $now)
				: null,
			Parameters::TIME => $reflectionSupport->hasMethodAnnotation($method, Parameters::TIME)
				? Parser::parseTimes((string) $reflectionSupport->getMethodAnnotation($method,Parameters::TIME))
				: null,
		];

		return $parameters;
	}

	public function getName(): string
	{
		return $this->values[Parameters::TASK];
	}

	public function isTask(): bool
	{
		return Strings::length($this->values[Parameters::TASK]) > 0;
	}

	/**
	 * Returns true if today is allowed day of week.
	 */
	public function isInDay(DateTimeInterface $now): bool
	{
		if (($days = $this->values[Parameters::DAYS]) !== null) {
			return in_array($now->format('D'), $days);
		}

		return true;
	}

	/**
	 * Returns true if today is allowed day of month.
	 */
	public function isInDayOfMonth(DateTimeInterface $now): bool
	{
		if (($days = $this->values[Parameters::DAYS_OF_MONTH]) !== null) {
			return in_array($now->format('j'), $days, true);
		}

		return true;
	}

	/**
	 * Returns true if current time is in allowed range.
	 */
	public function isInTime(DateTimeInterface $now): bool
	{
		if ($times = $this->values[Parameters::TIME]) {
			foreach ($times as $time) {
				if ($time['to'] && $time['to'] >= $now->format('H:i') && $time['from'] <= $now->format('H:i')) {
					// Is in range with precision to minutes
					return true;
				}
				if ($time['from'] === $now->format('H:i')) {
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
	 */
	public function isNextPeriod(DateTimeInterface $now, DateTimeInterface $lastRunTime = null): bool
	{
		if ($lastRunTime !== null && !$lastRunTime instanceof DateTimeImmutable && !$lastRunTime instanceof DateTime) {
			throw new InvalidArgumentException;
		}
		if (isset($this->values[Parameters::PERIOD]) && $this->values[Parameters::PERIOD]) {
			// Prevent run task on next cronner run because of a few seconds shift
			$now = Nette\Utils\DateTime::from($now)->modifyClone('+5 seconds');

			return $lastRunTime === null || $lastRunTime->modify('+ ' . $this->values[Parameters::PERIOD]) <= $now;
		}

		return true;
	}
}
