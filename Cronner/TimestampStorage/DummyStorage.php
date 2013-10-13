<?php

namespace stekycz\Cronner\TimestampStorage;

use DateTime;
use Nette\Object;
use stekycz\Cronner\ITimestampStorage;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class DummyStorage extends Object implements ITimestampStorage
{

	/**
	 * Sets name of current task.
	 *
	 * @param string|null $taskName
	 */
	public function setTaskName($taskName = NULL)
	{
		// Dummy
	}



	/**
	 * Saves current date and time as last invocation time.
	 *
	 * @param \DateTime $now
	 */
	public function saveRunTime(DateTime $now)
	{
		// Dummy
	}



	/**
	 * Returns date and time of last cron task invocation.
	 *
	 * @return \DateTime|null
	 */
	public function loadLastRunTime()
	{
		return NULL; // Dummy
	}

}
