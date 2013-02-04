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
			&& Strings::length($this->values[static::TASK]) > 0
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
		$taskName = null;
		if ($method->hasAnnotation(Parameters::TASK)) {
			$className = $method->getDeclaringClass()->getName();
			$methodName = $method->getName();
			$taskName = $className . ' - ' . $methodName;
		}

		$parameters = array(
			static::TASK => Parser::parseName($method->getAnnotation(Parameters::TASK))
				?: $taskName,
			static::PERIOD => $method->hasAnnotation(Parameters::PERIOD)
				? Parser::parsePeriod($method->getAnnotation(Parameters::PERIOD))
				: null,
			static::DAYS => $method->hasAnnotation(Parameters::DAYS)
				? Parser::parseDays($method->getAnnotation(Parameters::DAYS))
				: null,
			static::TIME => $method->hasAnnotation(Parameters::TIME)
				? Parser::parseTimes($method->getAnnotation(Parameters::TIME))
				: null,
		);

		return $parameters;
	}

}
