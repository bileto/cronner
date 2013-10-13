<?php

namespace stekycz\Cronner;

use DateTime;
use Exception;
use Nette;
use Nette\Diagnostics\Debugger;
use Nette\Object;
use Nette\Reflection\ClassType;
use Nette\Utils\Strings;
use ReflectionMethod;
use stekycz\Cronner\Tasks\Parameters;
use stekycz\Cronner\Tasks\Task;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
final class Processor extends Object
{

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
	 * @var \Nette\Callback
	 */
	private $logCallback;

	/**
	 * @var bool
	 */
	private $skipFailedTask = TRUE;



	/**
	 * @param \stekycz\Cronner\ITimestampStorage $timestampStorage
	 * @param bool $skipFailedTask
	 * @param callable|null $logCallback Callback should accept one argument (an exception object)
	 */
	public function __construct(
		ITimestampStorage $timestampStorage,
		$skipFailedTask = TRUE,
		$logCallback = NULL
	)
	{
		$this->timestampStorage = $timestampStorage;
		$this->skipFailedTask = (bool) $skipFailedTask;
		$this->setLogCallback($logCallback);
	}



	/**
	 * Sets log callback.
	 *
	 * @param callable|null $logCallback Callback should accept one argument (an exception object)
	 */
	public function setLogCallback($logCallback = NULL)
	{
		if ($logCallback === NULL) {
			$logCallback = function (Exception $exception) {
				Debugger::log($exception, Debugger::ERROR);
			};
		}
		$this->logCallback = callback($logCallback);
	}



	/**
	 * Adds task case to be processed when cronner runs. If tasks
	 * with name which is already added are given then throws
	 * an exception.
	 *
	 * @param object $tasks
	 * @return \stekycz\Cronner\Cronner
	 * @throws \stekycz\Cronner\InvalidArgumentException
	 */
	public function addTasks($tasks)
	{
		$tasksId = $this->createIdFromObject($tasks);
		if (in_array($tasksId, $this->registeredTaskObjects)) {
			throw new InvalidArgumentException("Tasks with ID '" . $tasksId . "' have been already added.");
		}

		$reflection = new ClassType($tasks);
		$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
		foreach ($methods as $method) {
			if (!Strings::startsWith($method->getName(), '__') && $method->hasAnnotation(Parameters::TASK)) {
				$this->tasks[] = new Task($tasks, $method, $this->timestampStorage);
			}
		}
		$this->registeredTaskObjects[] = $tasksId;

		return $this;
	}



	/**
	 * Runs all registered tasks.
	 *
	 * @param \DateTime $now
	 */
	public function process(DateTime $now = NULL)
	{
		if ($now === NULL) {
			$now = new Nette\DateTime();
		}

		foreach ($this->tasks as $task) {
			try {
				if ($task->shouldBeRun($now)) {
					$task();
				}
			} catch (Exception $e) {
				if ($e instanceof RuntimeException) {
					throw $e; // Throw exception if it is Cronner Runtime exception
				} elseif ($this->skipFailedTask === FALSE) {
					throw $e; // Throw exception if failed task should not be skipped
				}
				$this->logCallback->invoke($e);
			}
		}
	}



	/**
	 * Returns count of added task objects.
	 *
	 * @return int
	 */
	public function countTaskObjects()
	{
		return count($this->registeredTaskObjects);
	}



	/**
	 * Returns count of added tasks.
	 *
	 * @return int
	 */
	public function countTasks()
	{
		return count($this->tasks);
	}



	/**
	 * Creates and returns identification string for given object.
	 *
	 * @param object $tasks
	 * @return string
	 */
	private function createIdFromObject($tasks)
	{
		return sha1(get_class($tasks));
	}

}
