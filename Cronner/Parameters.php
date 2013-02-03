<?php

namespace stekycz\Cronner;

use Nette;
use Nette\Object;
use Nette\Reflection\Method;
use DateTime;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */
final class Parameters extends Object {

	const TASK = 'cronner-task';
	const TIME = 'cronner-time';
	const PERIOD = 'cronner-period';
	const DAYS = 'cronner-days';

	/**
	 * @var \Nette\Reflection\Method
	 */
	private $method;

	/**
	 * @param \Nette\Reflection\Method $method
	 */
	public function __construct(Method $method) {
		$this->method = $method;
	}

	/**
	 * Returns True if given method should be run.
	 *
	 * @param \DateTime $now
	 * @return bool
	 */
	public function shouldBeRun(DateTime $now = null) {
		return $this->isTask()
			&& $this->isInTime($now)
			&& $this->isNextPeriod();
	}

	private function isTask() {
		return $this->method->hasAnnotation(static::TASK);
	}

	private function isInTime(DateTime $now = null) {
		if ($now === null) {
			$now = new Nette\DateTime();
		}

		if ($this->method->hasAnnotation(static::DAYS)) {
			$annotation = $this->method->getAnnotation(static::DAYS);
		}
		if ($this->method->hasAnnotation(static::TIME)) {
			$annotation = $this->method->getAnnotation(static::TIME);
		}

		return true;
	}

	private function isNextPeriod() {
		if ($this->method->hasAnnotation(static::PERIOD)) {
			$annotation = $this->method->getAnnotation(static::PERIOD);
		}

		return true;
	}

}
