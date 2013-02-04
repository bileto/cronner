<?php

namespace stekycz\Cronner;

use DateTime;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-04
 */
interface ITimestampStorage {

	/**
	 * Saves current date and time as last invocation time.
	 */
	public function saveRunTime(DateTime $now);

	/**
	 * Returns date and time of last cron task invocation.
	 *
	 * @return \DateTime|null
	 */
	public function loadLastRunTime();

}
