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
	 * Adds callback which creates an instance of tasks.
	 *
	 * @param callable $callback
	 * @return \stekycz\Cronner\Cronner
	 * @throws \stekycz\Cronner\InvalidArgumentException
	 */
	public function addTasksCallback($callback) {
		if (!is_callable($callback)) {
			throw new InvalidArgumentException("Given tasks factory callback is not callable.");
		}
		$this->callbacks[] = $callback;
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
		$processor = new Processor();

		foreach ($this->callbacks as $callback) {
			$tasks = call_user_func($callback);
			$processor->addTaskCase($tasks);
		}

		$processor->process($now);
	}

}
