<?php

namespace stekycz\Cronner\tests\Tasks;

use PHPUnit_Framework_TestCase;
use DateTime;
use stekycz\Cronner\Tasks\Parameters;
use Nette;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */
class Parameters_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @test
	 * @dataProvider dataProviderGetName
	 * @param bool $expected
	 * @param array $parameters
	 */
	public function returnsTaskName($expected, array $parameters) {
		$params = new Parameters($parameters);
		$this->assertEquals($expected, $params->getName());
	}

	public function dataProviderGetName() {
		return array(
			array('Name of task', array(Parameters::TASK => 'Name of task',)),
			array('0', array(Parameters::TASK => '0',)),
			array('', array(Parameters::TASK => '   ',)),
			array('', array(Parameters::TASK => '',)),
			array('', array(Parameters::TASK => true,)),
			array('', array(Parameters::TASK => false,)),
			array('', array(Parameters::TASK => null,)),
			array('', array(Parameters::TASK => 0,)),
			array('', array()),
		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderIsTask
	 * @param bool $expected
	 * @param array $parameters
	 */
	public function detectsTask($expected, array $parameters) {
		$params = new Parameters($parameters);
		$this->assertEquals($expected, $params->isTask());
	}

	public function dataProviderIsTask() {
		return array(
			array(true, array(Parameters::TASK => 'Name of task',)),
			array(true, array(Parameters::TASK => '0',)),
			array(false, array(Parameters::TASK => '   ',)),
			array(false, array(Parameters::TASK => '',)),
			array(false, array(Parameters::TASK => true,)),
			array(false, array(Parameters::TASK => false,)),
			array(false, array(Parameters::TASK => null,)),
			array(false, array(Parameters::TASK => 0,)),
			array(false, array()),
		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderIsNextPeriod
	 * @param bool $expected
	 * @param \DateTime $now
	 * @param \DateTime|null $lastRunTime
	 * @param array $parameters
	 */
	public function detectsIfNowIsInNextPeriod($expected, DateTime $now, DateTime $lastRunTime = null, array $parameters) {
		$params = new Parameters($parameters);
		$this->assertEquals($expected, $params->isNextPeriod($now, $lastRunTime));
	}

	public function dataProviderIsNextPeriod() {
		return array(
			array(
				true,
				new Nette\DateTime('2013-02-03 17:00:00'),
				new Nette\DateTime('2013-02-03 16:54:59'),
				array(Parameters::PERIOD => '5 minutes',)
			),
			array(
				true,
				new Nette\DateTime('2013-02-03 17:00:00'),
				new Nette\DateTime('2013-02-03 16:55:00'),
				array(Parameters::PERIOD => '5 minutes',)
			),
			array(
				false,
				new Nette\DateTime('2013-02-03 17:00:00'),
				new Nette\DateTime('2013-02-03 16:55:01'),
				array(Parameters::PERIOD => '5 minutes',)
			),
			array(
				false,
				new Nette\DateTime('2013-02-03 17:00:00'),
				new Nette\DateTime('2013-02-03 16:55:01'),
				array(Parameters::PERIOD => '1 hour',)
			),
			array(
				true,
				new Nette\DateTime('2013-02-03 17:00:00'),
				new Nette\DateTime('2013-02-03 16:00:00'),
				array(Parameters::PERIOD => '1 hour',)
			),
			array(
				true,
				new Nette\DateTime('2013-02-03 17:00:00'),
				new Nette\DateTime('2013-02-03 16:00:00'),
				array()
			),
			array(
				true,
				new Nette\DateTime('2013-02-03 17:00:00'),
				null,
				array(Parameters::PERIOD => '1 hour',)
			),
			array(
				true,
				new Nette\DateTime('2013-02-03 17:00:00'),
				null,
				array()
			),
		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderIsInDay
	 * @param bool $expected
	 * @param array $parameters
	 * @param \DateTime $now
	 */
	public function detectsAllowedDaysOfWeek($expected, array $parameters, DateTime $now) {
		$params = new Parameters($parameters);
		$this->assertEquals($expected, $params->isInDay($now));
	}

	public function dataProviderIsInDay() {
		return array(
			// One day
			array(true, array(Parameters::DAYS => array('Mon',),), new Nette\DateTime('2013-02-11 12:34:56')),
			array(false, array(Parameters::DAYS => array('Mon',),), new Nette\DateTime('2013-02-12 12:34:56')),
			array(true, array(Parameters::DAYS => array('Tue',),), new Nette\DateTime('2013-02-12 12:34:56')),
			array(false, array(Parameters::DAYS => array('Tue',),), new Nette\DateTime('2013-02-13 12:34:56')),
			array(true, array(Parameters::DAYS => array('Wed',),), new Nette\DateTime('2013-02-13 12:34:56')),
			array(false, array(Parameters::DAYS => array('Wed',),), new Nette\DateTime('2013-02-14 12:34:56')),
			array(true, array(Parameters::DAYS => array('Thu',),), new Nette\DateTime('2013-02-14 12:34:56')),
			array(false, array(Parameters::DAYS => array('Thu',),), new Nette\DateTime('2013-02-15 12:34:56')),
			array(true, array(Parameters::DAYS => array('Fri',),), new Nette\DateTime('2013-02-15 12:34:56')),
			array(false, array(Parameters::DAYS => array('Fri',),), new Nette\DateTime('2013-02-16 12:34:56')),
			array(true, array(Parameters::DAYS => array('Sat',),), new Nette\DateTime('2013-02-16 12:34:56')),
			array(false, array(Parameters::DAYS => array('Sat',),), new Nette\DateTime('2013-02-17 12:34:56')),
			array(true, array(Parameters::DAYS => array('Sun',),), new Nette\DateTime('2013-02-17 12:34:56')),
			array(false, array(Parameters::DAYS => array('Sun',),), new Nette\DateTime('2013-02-18 12:34:56')),
			// Empty days
			array(false, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-11 12:34:56')),
			array(false, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-12 12:34:56')),
			array(false, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-13 12:34:56')),
			array(false, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-14 12:34:56')),
			array(false, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-15 12:34:56')),
			array(false, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-16 12:34:56')),
			array(false, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-17 12:34:56')),
			// Without days
			array(true, array(Parameters::DAYS => null,), new Nette\DateTime('2013-02-11 12:34:56')),
			array(true, array(Parameters::DAYS => null,), new Nette\DateTime('2013-02-12 12:34:56')),
			array(true, array(Parameters::DAYS => null,), new Nette\DateTime('2013-02-13 12:34:56')),
			array(true, array(Parameters::DAYS => null,), new Nette\DateTime('2013-02-14 12:34:56')),
			array(true, array(Parameters::DAYS => null,), new Nette\DateTime('2013-02-15 12:34:56')),
			array(true, array(Parameters::DAYS => null,), new Nette\DateTime('2013-02-16 12:34:56')),
			array(true, array(Parameters::DAYS => null,), new Nette\DateTime('2013-02-17 12:34:56')),
		);
	}

}
