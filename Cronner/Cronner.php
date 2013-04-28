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
	 * @var int Max execution time of PHP script in seconds
	 */
	private $maxExecutionTime;

	/**
	 * @var bool
	 */
	private $skipFailedTask = true;

	/**
	 * @param \stekycz\Cronner\ITimestampStorage $timestampStorage
	 * @param int|null $maxExecutionTime It is used only when Cronner runs
     * @param bool $skipFailedTask
     */
    public function __construct(
        ITimestampStorage $timestampStorage,
        $maxExecutionTime = null,
        $skipFailedTask = true
    ) {
		$this->setTimestampStorage($timestampStorage);
		$this->setMaxExecutionTime($maxExecutionTime);
        $this->setSkipFailedTask($skipFailedTask);
	}

	/**
	 * @param \stekycz\Cronner\ITimestampStorage $timestampStorage
	 */
	public function setTimestampStorage(ITimestampStorage $timestampStorage) {
		$this->timestampStorage = $timestampStorage;
	}

	/**
	 * Sets max execution time for Cronner. It is used only when Cronner runs.
	 *
	 * @param int|null $maxExecutionTime
	 * @throws \stekycz\Cronner\InvalidArgumentException
	 */
	public function setMaxExecutionTime($maxExecutionTime = null) {
		if ($maxExecutionTime !== null && (!is_numeric($maxExecutionTime) || ((int) $maxExecutionTime) <= 0)) {
			throw new InvalidArgumentException(
				"Max execution time must be NULL or numeric value. Type '" . gettype($maxExecutionTime) . "' was given."
			);
		}
		$this->maxExecutionTime = $maxExecutionTime;
	}

    /**
     * Sets flag that thrown exceptions will not be thrown but cached and logged.
     *
     * @param bool $skipFailedTask
     */
    public function setSkipFailedTask($skipFailedTask = true)
    {
        $this->skipFailedTask = (bool) $skipFailedTask;
    }

	/**
	 * Returns max execution time for Cronner. It does not load INI value.
	 *
	 * @return int|null
	 */
	public function getMaxExecutionTime() {
		return !is_null($this->maxExecutionTime) ? (int) $this->maxExecutionTime : null;
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
		$processor = new Processor($this->timestampStorage, $this->skipFailedTask);

		foreach ($this->callbacks as $callback) {
			$tasks = call_user_func($callback);
			$processor->addTasks($tasks);
		}

		if ($this->maxExecutionTime !== null) {
			set_time_limit((int) $this->maxExecutionTime);
		}
		$processor->process($now);
	}

}
