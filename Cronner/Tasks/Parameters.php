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
		$values[static::TASK] = isset($values[static::TASK]) && is_string($values[static::TASK])
			? Strings::trim($values[static::TASK])
			: '';
		$this->values = $values;
	}

	/**
	 * Returns name of task.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->values[static::TASK];
	}

	/**
	 * Returns true if task is really a task.
	 *
	 * @return bool
	 */
	public function isTask() {
		return Strings::length($this->values[static::TASK]) > 0;
	}

	/**
	 * Returns true if today is allowed day of week.
	 *
	 * @param \DateTime $now
	 * @return bool
	 */
	public function isInDay(DateTime $now) {
		if (($days = $this->values[static::DAYS]) !== null) {
			return in_array($now->format('D'), $days);
		}
		return true;
	}

	/**
	 * Returns true if current time is in allowed range.
	 *
	 * @param \DateTime $now
	 * @return bool
	 */
	public function isInTime(DateTime $now) {
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
	 *
	 * @param \DateTime $now
	 * @param \DateTime|null $lastRunTime
	 * @return bool
	 */
	public function isNextPeriod(DateTime $now, DateTime $lastRunTime = null) {
		if (isset($this->values[static::PERIOD]) && $this->values[static::PERIOD]) {
			return $lastRunTime === null || $lastRunTime->modify('+ ' . $this->values[static::PERIOD]) <= $now;
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
