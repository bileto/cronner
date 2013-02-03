<?php

namespace stekycz\Cronner;

use Nette\Reflection\Method;
use Nette\DateTime;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */
final class Parameters {

	const TASK = 'cronner-task';
	const TIME = 'cronner-time';
	const PERIOD = 'cronner-period';
	const DAYS = 'cronner-days';

	private function __construct() {
	}

	/**
	 * Returns True if given method should be run.
	 *
	 * @param \Nette\Reflection\Method $method
	 * @return bool
	 */
	public static function shouldBeRun(Method $method) {
		return static::isTask($method)
			&& static::isInTime($method)
			&& static::isNextPeriod($method);
	}

	private static function isTask(Method $method) {
		return $method->hasAnnotation(static::TASK);
	}

	private static function isInTime(Method $method) {
		$now = new DateTime();

		if ($method->hasAnnotation(static::DAYS)) {
			$annotation = $method->getAnnotation(static::DAYS);
		}
		if ($method->hasAnnotation(static::TIME)) {
			$annotation = $method->getAnnotation(static::TIME);
		}

		return true;
	}

	private static function isNextPeriod(Method $method) {
		if ($method->hasAnnotation(static::PERIOD)) {
			$annotation = $method->getAnnotation(static::PERIOD);
		}

		return true;
	}

}
