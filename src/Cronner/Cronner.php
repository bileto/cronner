<?php

declare(strict_types=1);

namespace stekycz\Cronner;

use Exception;
use Nette\Object;
use Nette\Reflection\ClassType;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use ReflectionMethod;
use stekycz\Cronner\Exceptions\DuplicateTaskNameException;
use stekycz\Cronner\Exceptions\InvalidArgumentException;
use stekycz\Cronner\Exceptions\RuntimeException;
use stekycz\Cronner\Tasks\Parameters;
use stekycz\Cronner\Tasks\Task;
use Tracy\Debugger;

/**
 * @method onTaskBegin(Cronner $cronner, Task $task)
 * @method onTaskFinished(Cronner $cronner, Task $task)
 * @method onTaskError(Cronner $cronner, Exception $exception, Task $task)
 */
class Cronner extends Object
{

	/**
	 * @var callable[]
	 */
	public $onTaskBegin = array();

	/**
	 * @var callable[]
	 */
	public $onTaskFinished = array();

	/**
	 * @var callable[]
	 */
	public $onTaskError = array();

	/**
	 * @var Task[]
	 */
	private $tasks = array();

	/**
	 * @var string[]
	 */
	private $registeredTaskObjects = array();

	/**
	 * @var ITimestampStorage
	 */
	private $timestampStorage;

	/**
	 * @var CriticalSection
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
	 * @param ITimestampStorage $timestampStorage
	 * @param CriticalSection $criticalSection
	 * @param int|null $maxExecutionTime It is used only when Cronner runs
	 * @param bool $skipFailedTask
	 */
	public function __construct(
		ITimestampStorage $timestampStorage,
		CriticalSection $criticalSection,
		int $maxExecutionTime = NULL,
		bool $skipFailedTask = TRUE
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
	 * @return Task[]
	 */
	public function getTasks() : array
	{
		return $this->tasks;
	}

	public function setTimestampStorage(ITimestampStorage $timestampStorage) : self
	{
		$this->timestampStorage = $timestampStorage;

		return $this;
	}

	/**
	 * Sets max execution time for Cronner. It is used only when Cronner runs.
	 *
	 * @param int|null $maxExecutionTime
	 * @return Cronner
	 * @throws InvalidArgumentException
	 */
	public function setMaxExecutionTime(int $maxExecutionTime = NULL) : self
	{
		if ($maxExecutionTime !== NULL && $maxExecutionTime <= 0) {
			throw new InvalidArgumentException("Max execution time must be NULL or non negative number.");
		}
		$this->maxExecutionTime = $maxExecutionTime;

		return $this;
	}

	/**
	 * Sets flag that thrown exceptions will not be thrown but cached and logged.
	 */
	public function setSkipFailedTask(bool $skipFailedTask = TRUE) : self
	{
		$this->skipFailedTask = $skipFailedTask;

		return $this;
	}

	/**
	 * Returns max execution time for Cronner. It does not load INI value.
	 *
	 * @return int|null
	 */
	public function getMaxExecutionTime()
	{
		return !is_null($this->maxExecutionTime) ? $this->maxExecutionTime : NULL;
	}

	/**
	 * Adds task case to be processed when cronner runs. If tasks
	 * with name which is already added are given then throws
	 * an exception.
	 *
	 * @param object $tasks
	 * @return Cronner
	 * @throws InvalidArgumentException
	 */
	public function addTasks($tasks) : self
	{
		$tasksId = $this->createIdFromObject($tasks);
		if (in_array($tasksId, $this->registeredTaskObjects)) {
			throw new InvalidArgumentException("Tasks with ID '" . $tasksId . "' have been already added.");
		}

		$reflection = new ClassType($tasks);
		$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
		foreach ($methods as $method) {
			if (!Strings::startsWith($method->getName(), '__') && $method->hasAnnotation(Parameters::TASK)) {
				$task = new Task($tasks, $method, $this->timestampStorage);
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
	 */
	public function run(DateTime $now = NULL)
	{
		if ($now === NULL) {
			$now = new DateTime();
		}
		if ($this->maxExecutionTime !== NULL) {
			set_time_limit($this->maxExecutionTime);
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
	 */
	public function countTaskObjects() : int
	{
		return count($this->registeredTaskObjects);
	}

	/**
	 * Returns count of added tasks.
	 */
	public function countTasks() : int
	{
		return count($this->tasks);
	}

	/**
	 * Creates and returns identification string for given object.
	 *
	 * @param object $tasks
	 * @return string
	 */
	private function createIdFromObject($tasks) : string
	{
		return sha1(get_class($tasks));
	}

}
