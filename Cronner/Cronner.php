<?php

namespace stekycz\Cronner;

use Exception;
use Nette\Utils\DateTime;
use Nette\Object;
use Nette\Reflection\ClassType;
use Nette\Utils\Strings;
use ReflectionMethod;
use stekycz\Cronner\Tasks\Parameters;
use stekycz\Cronner\Tasks\Task;
use Tracy\Debugger;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @method onTaskBegin(\stekycz\Cronner\Cronner $cronner, \stekycz\Cronner\Tasks\Task $task)
 * @method onTaskFinished(\stekycz\Cronner\Cronner $cronner, \stekycz\Cronner\Tasks\Task $task)
 * @method onTaskError(\stekycz\Cronner\Cronner $cronner, \Exception $exception, \stekycz\Cronner\Tasks\Task $task)
 */
class Cronner extends Object
{

	/**
	 * @var callable
	 */
	public $onTaskBegin = array();

	/**
	 * @var callable
	 */
	public $onTaskFinished = array();

	/**
	 * @var callable
	 */
	public $onTaskError = array();

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
	 * @var \stekycz\Cronner\CriticalSection
	 */
	private $criticalSection;

	/**
	 * @var int Max execution time of PHP script in seconds
	 */
	private $maxExecutionTime;

	/**
	 * @var bool
	 */
	private $skipFailedTask = TRUE;



	/**
	 * @param \stekycz\Cronner\ITimestampStorage $timestampStorage
	 * @param \stekycz\Cronner\CriticalSection $criticalSection
	 * @param int|null $maxExecutionTime It is used only when Cronner runs
	 * @param bool $skipFailedTask
	 */
	public function __construct(
		ITimestampStorage $timestampStorage,
		CriticalSection $criticalSection,
		$maxExecutionTime = NULL,
		$skipFailedTask = TRUE
	)
	{
		$this->setTimestampStorage($timestampStorage);
		$this->criticalSection = $criticalSection;
		$this->setMaxExecutionTime($maxExecutionTime);
		$this->setSkipFailedTask($skipFailedTask);
		$this->onTaskError[] = function (Cronner $cronner, Exception $exception) {
			Debugger::log($exception, Debugger::ERROR);
		};
	}

	/**
	 * @return \stekycz\Cronner\Tasks\Task[]
	 */
	public function getTasks()
	{
		return $this->tasks;
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
				$task =  new Task($tasks, $method, $this->timestampStorage);
				if (array_key_exists($task->getName(), $this->tasks)) {
					throw new DuplicateTaskNameException('Cannot use more tasks with the same name "' . $task->getName() . '".');
				}
				$this->tasks[$task->getName()] = $task;
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
				$name = $task->getName();
				if ($task->shouldBeRun($now)) {
					if ($this->criticalSection->enter($name)) {
						$this->onTaskBegin($this, $task);
						$task($now);
						$this->onTaskFinished($this, $task);
						$this->criticalSection->leave($name);
					}
				}
			} catch (Exception $e) {
				$this->onTaskError($this, $e, $task);
				$name = $task->getName();
				if ($this->criticalSection->isEntered($name)) {
					$this->criticalSection->leave($name);
				}
				if ($e instanceof RuntimeException) {
					throw $e; // Throw exception if it is Cronner Runtime exception
				} elseif ($this->skipFailedTask === FALSE) {
					throw $e; // Throw exception if failed task should not be skipped
				}
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
