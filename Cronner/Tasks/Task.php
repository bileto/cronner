<?php

namespace stekycz\Cronner\Tasks;

use DateTime;
use Nette;
use Nette\Object;
use Nette\Reflection\Method;
use stekycz\Cronner\ITimestampStorage;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
final class Task extends Object
{

	/**
	 * @var object
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
	private $parameters = NULL;



	/**
	 * Creates instance of one task.
	 *
	 * @param object $object
	 * @param \Nette\Reflection\Method $method
	 * @param \stekycz\Cronner\ITimestampStorage $timestampStorage
	 */
	public function __construct($object, Method $method, ITimestampStorage $timestampStorage)
	{
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
	public function shouldBeRun(DateTime $now = NULL)
	{
		if ($now === NULL) {
			$now = new Nette\DateTime();
		}

		$parameters = $this->getParameters();
		$this->timestampStorage->setTaskName($parameters->getName());

		return $parameters->isTask()
		&& $parameters->isInDay($now)
		&& $parameters->isInTime($now)
		&& $parameters->isNextPeriod($now, $this->timestampStorage->loadLastRunTime());
	}



	public function __invoke()
	{
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
	private function getParameters()
	{
		if ($this->parameters === NULL) {
			$this->parameters = new Parameters(Parameters::parseParameters($this->method));
		}

		return $this->parameters;
	}

}
