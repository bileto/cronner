<?php

namespace stekycz\Cronner\Tasks;

use DateTime;
use Nette;
use Nette\Object;
use Nette\Reflection\Method;
use Nette\Utils\Strings;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
final class Parameters extends Object
{

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
	public function __construct(array $values)
	{
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
	public function getName()
	{
		return $this->values[static::TASK];
	}



	/**
	 * Returns true if task is really a task.
	 *
	 * @return bool
	 */
	public function isTask()
	{
		return Strings::length($this->values[static::TASK]) > 0;
	}



	/**
	 * Returns true if today is allowed day of week.
	 *
	 * @param \DateTime $now
	 * @return bool
	 */
	public function isInDay(DateTime $now)
	{
		if (($days = $this->values[static::DAYS]) !== NULL) {
			return in_array($now->format('D'), $days);
		}

		return TRUE;
	}



	/**
	 * Returns true if current time is in allowed range.
	 *
	 * @param \DateTime $now
	 * @return bool
	 */
	public function isInTime(DateTime $now)
	{
		if ($times = $this->values[static::TIME]) {
			foreach ($times as $time) {
				if ($time['to'] && $time['to'] >= $now->format('H:i') && $time['from'] <= $now->format('H:i')) {
					// Is in range with precision to minutes
					return TRUE;
				} elseif ($time['from'] == $now->format('H:i')) {
					// Is in specific minute
					return TRUE;
				}
			}

			return FALSE;
		}

		return TRUE;
	}



	/**
	 * Returns true if current time is next period of invocation.
	 *
	 * @param \DateTime $now
	 * @param \DateTime|null $lastRunTime
	 * @return bool
	 */
	public function isNextPeriod(DateTime $now, DateTime $lastRunTime = NULL)
	{
		if (isset($this->values[static::PERIOD]) && $this->values[static::PERIOD]) {
			// Prevent run task on next cronner run because of a few seconds shift
			$now = Nette\Utils\DateTime::from($now)->modifyClone('+5 seconds');
			return $lastRunTime === NULL || $lastRunTime->modify('+ ' . $this->values[static::PERIOD]) <= $now;
		}

		return TRUE;
	}



	/**
	 * Parse cronner values from annotations.
	 *
	 * @param \Nette\Reflection\Method $method
	 * @return array
	 */
	public static function parseParameters(Method $method)
	{
		$taskName = NULL;
		if ($method->hasAnnotation(Parameters::TASK)) {
			$className = $method->getDeclaringClass()->getName();
			$methodName = $method->getName();
			$taskName = $className . ' - ' . $methodName;
		}

		$parameters = array(
			static::TASK => Parser::parseName($method->getAnnotation(Parameters::TASK))
					? : $taskName,
			static::PERIOD => $method->hasAnnotation(Parameters::PERIOD)
					? Parser::parsePeriod($method->getAnnotation(Parameters::PERIOD))
					: NULL,
			static::DAYS => $method->hasAnnotation(Parameters::DAYS)
					? Parser::parseDays($method->getAnnotation(Parameters::DAYS))
					: NULL,
			static::TIME => $method->hasAnnotation(Parameters::TIME)
					? Parser::parseTimes($method->getAnnotation(Parameters::TIME))
					: NULL,
		);

		return $parameters;
	}

}
