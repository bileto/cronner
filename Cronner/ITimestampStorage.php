<?php

namespace stekycz\Cronner;

use DateTime;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
interface ITimestampStorage
{

	/**
	 * Sets name of current task.
	 *
	 * @param string|null $taskName
	 */
	public function setTaskName($taskName = NULL);



	/**
	 * Saves current date and time as last invocation time.
	 *
	 * @param \DateTime $now
	 */
	public function saveRunTime(DateTime $now);



	/**
	 * Returns date and time of last cron task invocation.
	 *
	 * @return \DateTime|null
	 */
	public function loadLastRunTime();

}
