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
	 * @return string
	 */
	public function getObjectName()
	{
		return get_class($this->object);
	}



	/**
	 * @return \Nette\Reflection\Method
	 */
	public function getMethodReflection()
	{
		return $this->method;
	}



	/**
	 * @return string
	 */
	public function getObjectPath()
	{
		$reflection = new \ReflectionClass($this->object);

		return $reflection->getFileName();
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
			$now = new Nette\Utils\DateTime();
		}

		$parameters = $this->getParameters();
		if (!$parameters->isTask()) {
			return FALSE;
		}
		$this->timestampStorage->setTaskName($parameters->getName());

		return $parameters->isInDay($now)
			&& $parameters->isInTime($now)
			&& $parameters->isNextPeriod($now, $this->timestampStorage->loadLastRunTime());
	}



	/**
	 * Returns task name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->getParameters()->getName();
	}



	public function __invoke(\DateTime $now)
	{
		$this->method->invoke($this->object);
		$this->timestampStorage->setTaskName($this->getName());
		$this->timestampStorage->saveRunTime($now);
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
