<?php

namespace stekycz\Cronner\Tasks;

use Nette\Object;
use stekycz\Cronner\InvalidParameter;
use Nette\DateTime;
use Nette\Utils\Strings;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-04
 */
class Parser extends Object {

	/**
	 * Parses name of cron task.
	 *
	 * @param string $annotation
	 * @return string|null
	 */
	public static function parseName($annotation) {
		$name = null;
		if (is_string($annotation) && Strings::length($annotation)) {
			$name = Strings::trim($annotation);
		}
		return Strings::length($name) ? $name : null;
	}

	/**
	 * Parses period of cron task. If period is invalid throws exception.
	 *
	 * @param string $annotation
	 * @return string|null
	 * @throws \stekycz\Cronner\InvalidParameter
	 */
	public static function parsePeriod($annotation) {
		$period = null;
		if (!is_string($annotation)) {
			throw new InvalidParameter("Period parameter must be string.");
		}
		$annotation = Strings::trim($annotation);
		if (Strings::length($annotation)) {
			if (strtotime('+ ' . $annotation) === false) {
				throw new InvalidParameter("Given period parameter '" . $annotation . "' must be valid for strtotime().");
			}
			$period = $annotation;
		}
		return $period ?: null;
	}

	/**
	 * Parses allowed days for cron task. If one of days is invalid
	 * throws exception.
	 *
	 * @param string $annotation
	 * @return string[]|null
	 * @throws \stekycz\Cronner\InvalidParameter
	 */
	public static function parseDays($annotation) {
		$days = null;
		$annotation = Strings::trim($annotation);
		if ($annotation) {
			$validValues = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', );
			$workingDays = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', );
			$weekend = array('Sat', 'Sun', );

			$values = Strings::split($annotation, '/\s*,\s*/') ?: null;

			$days = array();
			foreach ($values as $value) {
				switch ($value) {
					case 'working days':
						$days = array_merge($days, $workingDays);
						break;
					case 'weekend':
						$days = array_merge($days, $weekend);
						break;
					default:
						$days[] = $value;
						break;
				}
			}
			$days = array_unique($days);
			foreach ($days as $day) {
				if (!in_array($day, $validValues)) {
					throw new InvalidParameter("Given day parameter '" . $day . "' must be one from " . implode(', ', $validValues) . ".");
				}
			}
		}
		return $days ?: null;
	}

	/**
	 * Parses allowed time ranges for cron task.
	 *
	 * @param string $annotation
	 * @return string[][]|null
	 */
	public static function parseTimes($annotation) {
		$times = null;
		$annotation = Strings::trim($annotation);
		if ($annotation) {
			$values = Strings::split($annotation, '/\s*,\s*/');
			if ($values) {
				$times = array();
				foreach ($values as $time) {
					$parts = Strings::split($time, '/\s*-\s*/');
					$times[] = array(
						'from' => $parts[0],
						'to' => $parts[1] ?: null,
					);
				}
			}
		}
		return $times ?: null;
	}

}
