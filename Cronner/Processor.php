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
	 * @var \stekycz\Cronner\Tasks\Task[]
	 */
	private $tasks = array();

	/**
	 * @var string[]
	 */
	private $registeredTaskObjects = array();

	/**
	 * @var \stekycz\Cronner\ITimestampStorage
	 */
	private $timestampStorage;

	/**
	 * @param \stekycz\Cronner\ITimestampStorage $timestampStorage
	 */
	public function __construct(ITimestampStorage $timestampStorage) {
		$this->timestampStorage = $timestampStorage;
	}

	/**
	 * Adds task case to be processed when cronner runs. If tasks
	 * with name which is already added are given then throws
	 * an exception.
	 *
	 * @param \stekycz\Cronner\Tasks $tasks
	 * @return \stekycz\Cronner\Cronner
	 * @throws \stekycz\Cronner\InvalidArgumentException
	 */
	public function addTasks(Tasks $tasks) {
		if (in_array($tasks->getName(), $this->registeredTaskObjects)) {
			throw new InvalidArgumentException("Tasks with name '" . $tasks->getName() . "' have been already added.");
		}

		$methods = $tasks->reflection->getMethods(ReflectionMethod::IS_PUBLIC);
		foreach ($methods as $method) {
			$this->tasks[] = new Task($tasks, $method, $this->timestampStorage);
		}
		$this->registeredTaskObjects[] = $tasks->getName();

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
			if ($task->shouldBeRun($now)) {
				$task();
			}
		}
	}

	/**
	 * Returns count of added task objects.
	 *
	 * @return int
	 */
	public function countTaskObjects() {
		return count($this->registeredTaskObjects);
	}

}
