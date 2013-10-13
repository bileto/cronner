<?php

namespace stekycz\Cronner;

use Exception;
use Nette\DateTime;
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
class Cronner extends Object
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
	 * @var int Max execution time of PHP script in seconds
	 */
	private $maxExecutionTime;

	/**
	 * @var bool
	 */
	private $skipFailedTask = TRUE;

	/**
	 * @var \Nette\Callback
	 */
	private $logCallback;



	/**
	 * @param \stekycz\Cronner\ITimestampStorage $timestampStorage
	 * @param int|null $maxExecutionTime It is used only when Cronner runs
	 * @param bool $skipFailedTask
	 * @param callable|null $logCallback Callback should accept one argument (an exception object)
	 */
	public function __construct(
		ITimestampStorage $timestampStorage,
		$maxExecutionTime = NULL,
		$skipFailedTask = TRUE,
		$logCallback = NULL
	)
	{
		$this->setTimestampStorage($timestampStorage);
		$this->setMaxExecutionTime($maxExecutionTime);
		$this->setSkipFailedTask($skipFailedTask);
		$this->setLogCallback($logCallback);
	}



	/**
	 * @param \stekycz\Cronner\ITimestampStorage $timestampStorage
	 * @return \stekycz\Cronner\Cronner
	 */
	public function setTimestampStorage(ITimestampStorage $timestampStorage)
	{
		$this->timestampStorage = $timestampStorage;

		return $this;
	}



	/**
	 * Sets max execution time for Cronner. It is used only when Cronner runs.
	 *
	 * @param int|null $maxExecutionTime
	 * @return \stekycz\Cronner\Cronner
	 * @throws \stekycz\Cronner\InvalidArgumentException
	 */
	public function setMaxExecutionTime($maxExecutionTime = NULL)
	{
		if ($maxExecutionTime !== NULL && (!is_numeric($maxExecutionTime) || ((int) $maxExecutionTime) <= 0)) {
			throw new InvalidArgumentException(
				"Max execution time must be NULL or numeric value. Type '" . gettype($maxExecutionTime) . "' was given."
			);
		}
		$this->maxExecutionTime = $maxExecutionTime;

		return $this;
	}



	/**
	 * Sets flag that thrown exceptions will not be thrown but cached and logged.
	 *
	 * @param bool $skipFailedTask
	 * @return \stekycz\Cronner\Cronner
	 */
	public function setSkipFailedTask($skipFailedTask = TRUE)
	{
		$this->skipFailedTask = (bool) $skipFailedTask;

		return $this;
	}



	/**
	 * Sets log callback.
	 *
	 * @param callable|null $logCallback Callback should accept one argument (an exception object)
	 * @return \stekycz\Cronner\Cronner
	 */
	public function setLogCallback($logCallback = NULL)
	{
		if ($logCallback === NULL) {
			$logCallback = function (Exception $exception) {
				Debugger::log($exception, Debugger::ERROR);
			};
		}
		$this->logCallback = callback($logCallback);

		return $this;
	}



	/**
	 * Returns max execution time for Cronner. It does not load INI value.
	 *
	 * @return int|null
	 */
	public function getMaxExecutionTime()
	{
		return !is_null($this->maxExecutionTime) ? (int) $this->maxExecutionTime : NULL;
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
	 * Runs all cron tasks.
	 *
	 * @param \DateTime $now
	 */
	public function run(\DateTime $now = NULL)
	{
		if ($now === NULL) {
			$now = new DateTime();
		}
		if ($this->maxExecutionTime !== NULL) {
			set_time_limit((int) $this->maxExecutionTime);
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
