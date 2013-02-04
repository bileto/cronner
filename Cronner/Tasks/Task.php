<?php

namespace stekycz\Cronner\Tasks;

use Nette;
use stekycz\Cronner\ITimestampStorage;
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
	 * @param \stekycz\Cronner\Tasks $object
	 * @param \Nette\Reflection\Method $method
	 * @param \stekycz\Cronner\ITimestampStorage $timestampStorage
	 */
	public function __construct(Tasks $object, Method $method, ITimestampStorage $timestampStorage) {
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
		$this->timestampStorage->saveRunTime(new Nette\DateTime());
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
