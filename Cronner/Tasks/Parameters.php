<?php

namespace stekycz\Cronner\Tasks;

use Nette;
use Nette\Utils\Strings;
use Nette\Object;
use Nette\Reflection\Method;
use DateTime;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */
final class Parameters extends Object {

	const TASK = 'cronner-task';
	const PERIOD = 'cronner-period';
	const DAYS = 'cronner-days';
	const TIME = 'cronner-time';

	/**
	 * @var array
	 */
	private $values;

	/**
	 * @param array $values
	 */
	public function __construct(array $values) {
		$this->values = $values;
	}

	/**
	 * Returns name of task.
	 *
	 * @return string
	 */
	public function getName() {
		return (string) $this->values[static::TASK];
	}

	/**
	 * Returns true if task is really a task.
	 *
	 * @return bool
	 */
	public function isTask() {
		return (
			is_string($this->values[static::TASK])
			&& Strings::length(Strings::trim($this->values[static::TASK])) > 0
		);
	}

	/**
	 * Returns true if today is allowed day of week.
	 *
	 * @param \DateTime $now
	 * @return bool
	 */
	public function isInDay(DateTime $now) {
		if ($this->values[static::DAYS]) {
			$annotation = $this->values[static::DAYS];
		}
		// TODO - implement
		return true;
	}

	/**
	 * Returns true if current time is in allowed range.
	 *
	 * @param \DateTime $now
	 * @return bool
	 */
	public function isInTime(DateTime $now) {
		if ($this->values[static::TIME]) {
			$annotation = $this->values[static::TIME];
		}
		// TODO - implement
		return true;
	}

	/**
	 * Returns true if current time is next period of invocation.
	 *
	 * @param \DateTime $now
	 * @param \DateTime|null $lastRunTime
	 * @return bool
	 */
	public function isNextPeriod(DateTime $now, DateTime $lastRunTime = null) {
		if ($this->values[static::PERIOD]) {
			return $lastRunTime->modify('+ ' . $this->values[static::PERIOD]) <= $now;
		}
		return true;
	}

	/**
	 * Parse cronner values from annotations.
	 *
	 * @param \Nette\Reflection\Method $method
	 * @return array
	 */
	public static function parseParameters(Method $method) {
		$parameters = array(
			static::TASK => null,
			static::PERIOD => null,
			static::DAYS => null,
			static::TIME => null,
		);

		if ($method->hasAnnotation(static::TASK)) {
			$className = $method->getDeclaringClass()->getName();
			$methodName = $method->getName();
			$annotation = $method->getAnnotation(static::TASK);
			$parameters[static::TASK] = $className . ' - ' . ($annotation ?: $methodName);
		}
		if ($method->hasAnnotation(static::PERIOD)) {
			$parameters[static::PERIOD] = $method->getAnnotation(static::PERIOD);
		}
		if ($method->hasAnnotation(static::DAYS)) {
			$parameters[static::DAYS] = $method->getAnnotation(static::DAYS);
		}
		if ($method->hasAnnotation(static::TIME)) {
			$parameters[static::TIME] = $method->getAnnotation(static::TIME);
		}

		return $parameters;
	}

}
