<?php

declare(strict_types=1);

namespace Bileto\Cronner;

use Exception;
use Nette\Reflection\ClassType;
use DateTimeInterface;
use DateTime;
use Nette\SmartObject;
use Nette\Utils\Strings;
use ReflectionException;
use ReflectionMethod;
use Bileto\CriticalSection\ICriticalSection;
use Bileto\Cronner\Exceptions\DuplicateTaskNameException;
use Bileto\Cronner\Exceptions\InvalidArgumentException;
use Bileto\Cronner\Exceptions\RuntimeException;
use Bileto\Cronner\Tasks\Parameters;
use Bileto\Cronner\Tasks\Task;
use Tracy\Debugger;

/**
 * @method void onTaskBegin(Cronner $cronner, Task $task)
 * @method void onTaskFinished(Cronner $cronner, Task $task)
 * @method void onTaskError(Cronner $cronner, Exception $exception, Task $task)
 */
class Cronner
{
    use SmartObject;

    /** @var callable[] */
    public $onTaskBegin = [];

    /** @var callable[] */
    public $onTaskFinished = [];

    /** @var callable[] */
    public $onTaskError = [];

    /** @var Task[] */
    private $tasks = [];

    /** @var string[] */
    private $registeredTaskObjects = array();

    /** @var ITimestampStorage */
    private $timestampStorage;

    /** @var ICriticalSection */
    private $criticalSection;

    /** @var int|null Max execution time of PHP script in seconds */
    private $maxExecutionTime;

    /** @var bool */
    private $skipFailedTask = true;

    /**
     * @param ITimestampStorage $timestampStorage
     * @param ICriticalSection $criticalSection
     * @param int|null $maxExecutionTime It is used only when Cronner runs
     * @param bool $skipFailedTask
     */
    public function __construct(
        ITimestampStorage $timestampStorage,
        ICriticalSection $criticalSection,
        int $maxExecutionTime = null,
        bool $skipFailedTask = true
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
     * @return array|Task[]
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    public function setTimestampStorage(ITimestampStorage $timestampStorage): self
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
    public function setMaxExecutionTime(int $maxExecutionTime = NULL): self
    {
        if ($maxExecutionTime !== null && $maxExecutionTime <= 0) {
            throw new InvalidArgumentException("Max execution time must be NULL or non negative number.");
        }
        $this->maxExecutionTime = $maxExecutionTime;

        return $this;
    }

    /**
     * Sets flag that thrown exceptions will not be thrown but cached and logged.
     * @param bool $skipFailedTask
     * @return Cronner
     */
    public function setSkipFailedTask(bool $skipFailedTask = true): self
    {
        $this->skipFailedTask = $skipFailedTask;

        return $this;
    }

    /**
     * Returns max execution time for Cronner. It does not load INI value.
     *
     * @return int|null
     */
    public function getMaxExecutionTime(): ?int
    {
        return !is_null($this->maxExecutionTime) ? $this->maxExecutionTime : null;
    }

    /**
     * Adds task case to be processed when cronner runs. If tasks
     * with name which is already added are given then throws
     * an exception.
     *
     * @param object $tasks
     * @return Cronner
     * @throws InvalidArgumentException|ReflectionException
     */
    public function addTasks($tasks): self
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
     * @param DateTimeInterface|null $now
     * @throws Exception
     */
    public function run(DateTimeInterface $now = null)
    {
        if ($now === null) {
            $now = new DateTime();
        }
        if ($this->maxExecutionTime !== null) {
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
                } elseif ($this->skipFailedTask === false) {
                    throw $e; // Throw exception if failed task should not be skipped
                }
            }
        }
    }

    /**
     * Returns count of added task objects.
     */
    public function countTaskObjects(): int
    {
        return count($this->registeredTaskObjects);
    }

    /**
     * Returns count of added tasks.
     */
    public function countTasks(): int
    {
        return count($this->tasks);
    }

    /**
     * Creates and returns identification string for given object.
     *
     * @param object $tasks
     * @return string
     */
    private function createIdFromObject($tasks): string
    {
        return sha1(get_class($tasks));
    }

}
