<?php

namespace stekycz\Cronner\Tasks;

use Nette;
use stekycz\Cronner\Tasks;
use DateTime;
use Nette\Object;
use Nette\Reflection\Method;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */
final class Task extends Object {

	/**
	 * @var \stekycz\Cronner\Tasks
	 */
	private $object;

	/**
	 * @var \Nette\Reflection\Method
	 */
	private $method;

	/**
	 * @var \stekycz\Cronner\Tasks\Parameters|null
	 */
	private $parameters = null;

	/**
	 * Creates instance of one task.
	 *
	 * @param \stekycz\Cronner\Tasks $tasks
	 * @param \Nette\Reflection\Method $method
	 */
	public function __construct(Tasks $tasks, Method $method) {
		$this->object = $tasks;
		$this->method = $method;
	}

	/**
	 * Returns True if given parameters should be run.
	 *
	 * @param \DateTime $now
	 * @return bool
	 */
	public function shouldBeRun(DateTime $now = null) {
		if ($now === null) {
			$now = new Nette\DateTime();
		}

		$parameters = $this->getParameters();
		return $parameters->isTask()
			&& $parameters->isInDay($now)
			&& $parameters->isInTime($now)
			&& $parameters->isNextPeriod($now, $this->loadLastRunTime());
	}

	public function __invoke() {
		$this->method->invoke($this->object);
		$this->saveRunTime();
	}

	/**
	 * Returns instance of parsed parameters.
	 *
	 * @return \stekycz\Cronner\Tasks\Parameters
	 */
	private function getParameters() {
		if ($this->parameters === null) {
			$this->parameters = new Parameters(Parameters::parseParameters($this->method));
		}
		return $this->parameters;
	}

	/**
	 * Saves current date and time as last invocation time.
	 */
	private function saveRunTime() {
		// TODO - save current date & time
	}

	/**
	 * Returns date and time of last cron task invocation.
	 *
	 * @return \DateTime
	 */
	private function loadLastRunTime() {
		// TODO - load date & time of last invocation
		return new Nette\DateTime('2013-01-01 00:00:00');
	}

}
