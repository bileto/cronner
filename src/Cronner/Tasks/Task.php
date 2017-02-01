<?php

declare(strict_types=1);

namespace stekycz\Cronner\Tasks;

use DateTime;
use Nette;
use Nette\Object;
use Nette\Reflection\Method;
use ReflectionClass;
use stekycz\Cronner\ITimestampStorage;

final class Task extends Object
{

	/**
	 * @var object
	 */
	private $object;

	/**
	 * @var Method
	 */
	private $method;

	/**
	 * @var ITimestampStorage
	 */
	private $timestampStorage;

	/**
	 * @var Parameters|null
	 */
	private $parameters = NULL;

	/**
	 * Creates instance of one task.
	 *
	 * @param object $object
	 * @param Method $method
	 * @param ITimestampStorage $timestampStorage
	 */
	public function __construct($object, Method $method, ITimestampStorage $timestampStorage)
	{
		$this->object = $object;
		$this->method = $method;
		$this->timestampStorage = $timestampStorage;
	}

	public function getObjectName() : string
	{
		return get_class($this->object);
	}

	public function getMethodReflection() : Method
	{
		return $this->method;
	}

	public function getObjectPath() : string
	{
		$reflection = new ReflectionClass($this->object);

		return $reflection->getFileName();
	}

	/**
	 * Returns True if given parameters should be run.
	 */
	public function shouldBeRun(DateTime $now = NULL) : bool
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

	public function getName() : string
	{
		return $this->getParameters()->getName();
	}

	public function __invoke(DateTime $now)
	{
		$this->method->invoke($this->object);
		$this->timestampStorage->setTaskName($this->getName());
		$this->timestampStorage->saveRunTime($now);
		$this->timestampStorage->setTaskName();
	}

	/**
	 * Returns instance of parsed parameters.
	 */
	private function getParameters() : Parameters
	{
		if ($this->parameters === NULL) {
			$this->parameters = new Parameters(Parameters::parseParameters($this->method));
		}

		return $this->parameters;
	}

}
