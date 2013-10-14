<?php

namespace stekycz\Cronner;

use Nette\Object;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class CriticalSection extends Object
{

	/**
	 * @var resource|NULL
	 */
	private static $handle = NULL;



	/**
	 * Enters critical section.
	 *
	 * @return bool
	 */
	public static function enter()
	{
		if (self::isEntered()) {
			return FALSE;
		}

		$handle = fopen(__FILE__, "a+");
		if ($handle === FALSE) {
			return FALSE;
		}

		$locked = flock($handle, LOCK_EX);
		if ($locked === FALSE) {
			fclose($handle);
			return FALSE;
		}
		self::$handle = $handle;

		return TRUE;
	}



	/**
	 * Leaves critical section.
	 *
	 * @return bool
	 */
	public static function leave()
	{
		if (!self::isEntered()) {
			return FALSE;
		}

		$unlocked = flock(self::$handle, LOCK_UN);
		if ($unlocked === FALSE) {
			return FALSE;
		}

		self::$handle = NULL;
		fclose(self::$handle);

		return TRUE;
	}



	/**
	 * Returns TRUE if critical section is entered.
	 *
	 * @return bool
	 */
	public static function isEntered()
	{
		return self::$handle !== NULL;
	}

}
