<?php

namespace stekycz\Cronner;

use Nette\Object;

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
	 */
	public function addTasksCallback($callback) {
		$this->callbacks[] = $callback;

		return $this;
	}

	/**
	 * Runs all cron tasks.
	 */
	public function run() {
		$processor = new Processor();
		foreach ($this->callbacks as $callback) {
			$tasks = call_user_func($callback);
			$processor->addTaskCase($tasks);
		}
		$processor->process();
	}

}
