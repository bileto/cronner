<?php

declare(strict_types=1);

namespace stekycz\Cronner\Tasks;

use Nette\Utils\Strings;
use stekycz\Cronner\Exceptions\InvalidParameterException;

class Parser
{
	use \Nette\SmartObject;

	/**
	 * Parses name of cron task.
	 *
	 * @param string $annotation
	 * @return string|null
	 */
	public static function parseName(string $annotation)
	{
		$name = Strings::trim($annotation);
		$name = Strings::length($name) > 0 ? $name : NULL;

		return $name;
	}

	/**
	 * Parses period of cron task. If annotation is invalid throws exception.
	 *
	 * @param string $annotation
	 * @return string|null
	 * @throws InvalidParameterException
	 */
	public static function parsePeriod(string $annotation)
	{
		$period = NULL;
		$annotation = Strings::trim($annotation);
		if (Strings::length($annotation)) {
			if (strtotime('+ ' . $annotation) === FALSE) {
				throw new InvalidParameterException(
					"Given period parameter '" . $annotation . "' must be valid for strtotime() with '+' sign as its prefix (added by Cronner automatically)."
				);
			}
			$period = $annotation;
		}

		return $period ?: NULL;
	}

	/**
	 * Parses allowed days for cron task. If annotation is invalid
	 * throws exception.
	 *
	 * @param string $annotation
	 * @return string[]|null
	 * @throws InvalidParameterException
	 */
	public static function parseDays(string $annotation)
	{
		static $validValues = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',];

		$days = NULL;
		$annotation = Strings::trim($annotation);
		if (Strings::length($annotation)) {
			$days = static::translateToDayNames($annotation);
			$days = static::expandDaysRange($days);
			foreach ($days as $day) {
				if (!in_array($day, $validValues)) {
					throw new InvalidParameterException(
						"Given day parameter '" . $day . "' must be one from " . implode(', ', $validValues) . "."
					);
				}
			}
			$days = array_values(array_intersect($validValues, $days));
		}

		return $days ?: NULL;
	}

	/**
	 * Parses allowed time ranges for cron task. If annotation is invalid
	 * throws exception.
	 *
	 * @param string $annotation
	 * @return string[][]|null
	 * @throws InvalidParameterException
	 */
	public static function parseTimes(string $annotation)
	{
		$times = NULL;
		$annotation = Strings::trim($annotation);
		if (Strings::length($annotation)) {
			if ($values = static::splitMultipleValues($annotation)) {
				$times = [];
				foreach ($values as $time) {
					$times = array_merge($times, static::parseOneTime($time));
				}
				usort($times, function ($a, $b) {
					return $a < $b ? -1 : ($a > $b ? 1 : 0);
				});
			}
		}

		return $times ?: NULL;
	}

	/**
	 * Translates given annotation to day names.
	 *
	 * @param string $annotation
	 * @return string[]
	 */
	private static function translateToDayNames(string $annotation) : array
	{
		static $workingDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri',];
		static $weekend = ['Sat', 'Sun',];

		$days = [];
		foreach (static::splitMultipleValues($annotation) as $value) {
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

		return array_unique($days);
	}

	/**
	 * Expands given day names and day ranges to day names only. The day range must be
	 * in "Mon-Fri" format.
	 *
	 * @param string[] $days
	 * @return string[]
	 */
	private static function expandDaysRange(array $days) : array
	{
		static $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',];
		$expandedValues = [];

		foreach ($days as $day) {
			if (Strings::match($day, '~^\w{3}\s*-\s*\w{3}$~u')) {
				list($begin, $end) = Strings::split($day, '~\s*-\s*~');
				$started = FALSE;
				foreach ($dayNames as $dayName) {
					if ($dayName === $begin) {
						$started = TRUE;
					}
					if ($started) {
						$expandedValues[] = $dayName;
					}
					if ($dayName === $end) {
						$started = FALSE;
					}
				}
			} else {
				$expandedValues[] = $day;
			}
		}

		return array_unique($expandedValues);
	}

	/**
	 * Splits given annotation by comma into array.
	 *
	 * @param string $annotation
	 * @return string[]
	 */
	private static function splitMultipleValues(string $annotation) : array
	{
		return Strings::split($annotation, '/\s*,\s*/');
	}

	/**
	 * Returns True if time in valid format is given, False otherwise.
	 */
	private static function isValidTime(string $time) : bool
	{
		return (bool) Strings::match($time, '/^\d{2}:\d{2}$/u');
	}

	/**
	 * Parses one time annotation. If it is invalid throws exception.
	 *
	 * @param string $time
	 * @return string[][]
	 * @throws InvalidParameterException
	 */
	private static function parseOneTime(string $time) : array
	{
		$time = static::translateToTimes($time);
		$parts = Strings::split($time, '/\s*-\s*/');
		if (!static::isValidTime($parts[0]) || (isset($parts[1]) && !static::isValidTime($parts[1]))) {
			throw new InvalidParameterException(
				"Times annotation is not in valid format. It must looks like 'hh:mm[ - hh:mm]' but '" . $time . "' was given."
			);
		}
		$times = [];
		if (static::isTimeOverMidnight($parts[0], isset($parts[1]) ? $parts[1] : NULL)) {
			$times[] = static::timePartsToArray('00:00', $parts[1]);
			$times[] = static::timePartsToArray($parts[0], '23:59');
		} else {
			$times[] = static::timePartsToArray($parts[0], isset($parts[1]) ? $parts[1] : NULL);
		}

		return $times;
	}

	/**
	 * Translates given annotation to day names.
	 */
	private static function translateToTimes(string $time) : string
	{
		static $translationMap = [
			'morning' => '06:00 - 11:59',
			'noon' => '12:00 - 12:29',
			'afternoon' => '12:30 - 16:59',
			'evening' => '17:00 - 21:59',
			'night' => '22:00 - 05:59',
			'midnight' => '00:00 - 00:29',
		];

		return array_key_exists($time, $translationMap) ? $translationMap[$time] : $time;
	}

	/**
	 * Returns True if given times includes midnight, False otherwise.
	 */
	private static function isTimeOverMidnight(string $from, string $to = NULL) : bool
	{
		return $to !== NULL && $to < $from;
	}

	/**
	 * Returns array structure with given times.
	 */
	private static function timePartsToArray(string $from, string $to = NULL) : array
	{
		return [
			'from' => $from,
			'to' => $to,
		];
	}

}
