<?php

namespace stekycz\Cronner\Tasks;

use Nette;
use stekycz\Cronner\ITasksContainer;
use stekycz\Cronner\ITimestampStorage;
use DateTime;
use Nette\Object;
use Nette\Reflection\Method;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */
final class Task extends Object {

	/**
	 * @var \stekycz\Cronner\ITasksContainer
	 */
	private $object;

	/**
	 * @var \Nette\Reflection\Method
	 */
	private $method;

	/**
	 * @var \stekycz\Cronner\ITimestampStorage
	 */
	private $timestampStorage;

	/**
	 * @var \stekycz\Cronner\Tasks\Parameters|null
	 */
	private $parameters = null;

	/**
	 * Creates instance of one task.
	 *
	 * @param \stekycz\Cronner\ITasksContainer $object
	 * @param \Nette\Reflection\Method $method
	 * @param \stekycz\Cronner\ITimestampStorage $timestampStorage
	 */
	public function __construct(ITasksContainer $object, Method $method, ITimestampStorage $timestampStorage) {
		$this->object = $object;
		$this->method = $method;
		$this->timestampStorage = $timestampStorage;
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
			&& $parameters->isNextPeriod($now, $this->timestampStorage->loadLastRunTime());
	}

	public function __invoke() {
		$this->method->invoke($this->object);
		$this->timestampStorage->setTaskName($this->getParameters()->getName());
		$this->timestampStorage->saveRunTime(new Nette\DateTime());
		$this->timestampStorage->setTaskName();
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

}
