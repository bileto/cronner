<?php

namespace stekycz\Cronner;

use Nette;
use stekycz\Cronner\Tasks\Task;
use Nette\Object;
use DateTime;
use ReflectionMethod;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */
final class Processor extends Object {

	/**
	 * @var \stekycz\Cronner\Tasks[]
	 */
	private $tasks = array();

	/**
	 * Adds task case to be processed when cronner runs. If tasks
	 * with name which is already added are given then throws
	 * an exception.
	 *
	 * @param \stekycz\Cronner\Tasks $tasks
	 * @return \stekycz\Cronner\Cronner
	 * @throws \stekycz\Cronner\InvalidArgumentException
	 */
	public function addTaskCase(Tasks $tasks) {
		if (array_key_exists($tasks->getName(), $this->tasks)) {
			throw new InvalidArgumentException("Tasks with name '" . $tasks->getName() . "' have been already added.");
		}
		$this->tasks[$tasks->getName()] = $tasks;
		return $this;
	}

	/**
	 * Runs all registered tasks.
	 *
	 * @param \DateTime $now
	 */
	public function process(DateTime $now = null) {
		if ($now === null) {
			$now = new Nette\DateTime();
		}

		foreach ($this->tasks as $task) {
			$this->processTasks($task, $now);
		}
	}

	/**
	 * Processes all tasks in given object.
	 *
	 * @param \stekycz\Cronner\Tasks $tasks
	 * @param \DateTime $now
	 */
	private function processTasks(Tasks $tasks, DateTime $now) {
		$methods = $tasks->reflection->getMethods(ReflectionMethod::IS_PUBLIC);
		foreach ($methods as $method) {
			$task = new Task($tasks, $method);
			if ($task->shouldBeRun($now)) {
				$task();
			}
		}
	}

}
