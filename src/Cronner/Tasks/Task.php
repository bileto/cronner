<?php

declare(strict_types=1);

namespace stekycz\Cronner\Tasks;

use DateTime;
use Nette;
use Nette\Reflection\Method;
use ReflectionClass;
use stekycz\Cronner\ITimestampStorage;
use stekycz\Cronner\Tasks\Parameters;

final class Task
{
	use \Nette\SmartObject;

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
			$now = new DateTime();
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

	/**
	 * @return DateTime|null
	 */
	public function getLastRun()
	{
		$this->timestampStorage->setTaskName($this->getName());
		return $this->timestampStorage->loadLastRunTime();
	}

	/**
	 * @param DateTime|null $startDate
	 * @return DateTime
	 */
	public function getNextRun(DateTime $startDate = NULL) : DateTime
	{
		$startDate = is_null($startDate) ? new DateTime() : clone  $startDate;

		$days = [
			1 => 'Mon',
			2 => 'Tue',
			3 => 'Wed',
			4 => 'Thu',
			5 => 'Fri',
			6 => 'Sat',
			7 => 'Sun',
		];

		$lastRun = $this->getLastRun();

		$parameters = Parameters::parseParameters($this->getMethodReflection());

		if (is_null($parameters[Parameters::PERIOD]) && is_null($parameters[Parameters::DAYS]) && is_null($parameters[Parameters::TIME])) {
			return $startDate;
		}

		if(is_null($parameters[Parameters::DAYS])) {
			$parameters[Parameters::DAYS] = $days;
		}

		if(is_null($parameters[Parameters::TIME])) {
			$parameters[Parameters::TIME][] = [
				'from' => '00:00',
				'to' => NULL,
			];
		}

		$nextRun = $startDate;

		if (!is_null($parameters[Parameters::PERIOD]) && !is_null($lastRun)) {
			$nextRun = $lastRun->modify(sprintf('+ %s', $parameters[Parameters::PERIOD]));
			$nextRun = $nextRun < $startDate ? $startDate : $nextRun;
		}

		$time = $nextRun->format('H:i');
		$day = $nextRun->format('N');
		$seconds = $nextRun->format('s');

		$nextTimes = array_filter(
			$parameters[Parameters::TIME],
			function ($definedTimes) use ($nextRun) {
				return $definedTimes['to'] === NULL || sprintf('%s:00', $definedTimes['to']) >= $nextRun->format('H:i:s');
			});

		if (in_array($nextRun->format('D'), $parameters[Parameters::DAYS]) && !empty($nextTimes)) {
			$nextTime = reset($nextTimes)['from'];

			if($nextTime <= $time) {
				$nextTime = $time;
			} else {
				$seconds = 0;
			}

			$timeParts = explode(':', $nextTime);
			$nextRun->setTime((int) $timeParts[0], (int) $timeParts[1], (int) $seconds);

		} else {
			$day++;
			$day = $day > 7 ? 1 : $day;

			while(!in_array($days[$day], $parameters[Parameters::DAYS])) {
				$day = ($day + 1) > 7 ? 0 : $day;
				$day++;
			}

			$nextRun->modify(sprintf('next %s', $days[$day]));
			$timeParts = explode(':', reset($parameters[Parameters::TIME])['from']);
			$nextRun->setTime((int) $timeParts[0], (int) $timeParts[1], 0);
		}

		return $nextRun;
	}
}
