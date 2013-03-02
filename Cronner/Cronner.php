<?php

namespace stekycz\Cronner;

use Nette;
use Nette\Object;
use DateTime;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */
class Cronner extends Object {

	/**
	 * @var callable[]
	 */
	protected $callbacks = array();

	/**
	 * @var \stekycz\Cronner\ITimestampStorage
	 */
	private $timestampStorage;

	/**
	 * @param \stekycz\Cronner\ITimestampStorage $timestampStorage
	 */
	public function __construct(ITimestampStorage $timestampStorage) {
		$this->setTimestampStorage($timestampStorage);
	}

	/**
	 * @param \stekycz\Cronner\ITimestampStorage $timestampStorage
	 */
	public function setTimestampStorage(ITimestampStorage $timestampStorage) {
		$this->timestampStorage = $timestampStorage;
	}

	/**
	 * Adds callback which creates an instance of tasks.
	 *
	 * @param callable $callback
	 * @return \stekycz\Cronner\Cronner
	 */
	public function addTasksCallback($callback) {
		$this->callbacks[] = callback($callback);
		return $this;
	}

	/**
	 * Runs all cron tasks.
	 *
	 * @param \DateTime $now
	 */
	public function run(DateTime $now = null) {
		if ($now === null) {
			$now = new Nette\DateTime();
		}
		$processor = new Processor($this->timestampStorage);

		foreach ($this->callbacks as $callback) {
			$tasks = call_user_func($callback);
			$processor->addTasks($tasks);
		}

		$processor->process($now);
	}

}
