<?php

/**
 * @testCase
 */

namespace stekycz\Cronner\tests\Tasks;

use DateTime;
use Nette;
use stekycz\Cronner\Tasks\Parameters;
use Tester\Assert;



require_once(__DIR__ . "/../bootstrap.php");

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class ParametersTest extends \TestCase
{

	/**
	 * @dataProvider dataProviderGetName
	 * @param bool $expected
	 * @param array $parameters
	 */
	public function testReturnsTaskName($expected, array $parameters)
	{
		$params = new Parameters($parameters);
		Assert::equal($expected, $params->getName());
	}



	public function dataProviderGetName()
	{
		return array(
			array('Name of task', array(Parameters::TASK => 'Name of task',)),
			array('0', array(Parameters::TASK => '0',)),
			array('', array(Parameters::TASK => '   ',)),
			array('', array(Parameters::TASK => '',)),
			array('', array(Parameters::TASK => TRUE,)),
			array('', array(Parameters::TASK => FALSE,)),
			array('', array(Parameters::TASK => NULL,)),
			array('', array(Parameters::TASK => 0,)),
			array('', array()),
		);
	}



	/**
	 * @dataProvider dataProviderIsTask
	 * @param bool $expected
	 * @param array $parameters
	 */
	public function testDetectsTask($expected, array $parameters)
	{
		$params = new Parameters($parameters);
		Assert::equal($expected, $params->isTask());
	}



	public function dataProviderIsTask()
	{
		return array(
			array(TRUE, array(Parameters::TASK => 'Name of task',)),
			array(TRUE, array(Parameters::TASK => '0',)),
			array(FALSE, array(Parameters::TASK => '   ',)),
			array(FALSE, array(Parameters::TASK => '',)),
			array(FALSE, array(Parameters::TASK => TRUE,)),
			array(FALSE, array(Parameters::TASK => FALSE,)),
			array(FALSE, array(Parameters::TASK => NULL,)),
			array(FALSE, array(Parameters::TASK => 0,)),
			array(FALSE, array()),
		);
	}



	/**
	 * @dataProvider dataProviderIsNextPeriod
	 * @param bool $expected
	 * @param \DateTime $now
	 * @param \DateTime|null $lastRunTime
	 * @param array $parameters
	 */
	public function testDetectsIfNowIsInNextPeriod($expected, DateTime $now, DateTime $lastRunTime = NULL, array $parameters)
	{
		$params = new Parameters($parameters);
		Assert::equal($expected, $params->isNextPeriod($now, $lastRunTime));
	}



	public function dataProviderIsNextPeriod()
	{
		return array(
			array(
				TRUE,
				new Nette\DateTime('2013-02-03 17:00:00'),
				new Nette\DateTime('2013-02-03 16:54:59'),
				array(Parameters::PERIOD => '5 minutes',)
			),
			array(
				TRUE,
				new Nette\DateTime('2013-02-03 17:00:00'),
				new Nette\DateTime('2013-02-03 16:55:00'),
				array(Parameters::PERIOD => '5 minutes',)
			),
			array(
				FALSE,
				new Nette\DateTime('2013-02-03 17:00:00'),
				new Nette\DateTime('2013-02-03 16:55:01'),
				array(Parameters::PERIOD => '5 minutes',)
			),
			array(
				FALSE,
				new Nette\DateTime('2013-02-03 17:00:00'),
				new Nette\DateTime('2013-02-03 16:55:01'),
				array(Parameters::PERIOD => '1 hour',)
			),
			array(
				TRUE,
				new Nette\DateTime('2013-02-03 17:00:00'),
				new Nette\DateTime('2013-02-03 16:00:00'),
				array(Parameters::PERIOD => '1 hour',)
			),
			array(
				TRUE,
				new Nette\DateTime('2013-02-03 17:00:00'),
				new Nette\DateTime('2013-02-03 16:00:00'),
				array()
			),
			array(
				TRUE,
				new Nette\DateTime('2013-02-03 17:00:00'),
				NULL,
				array(Parameters::PERIOD => '1 hour',)
			),
			array(
				TRUE,
				new Nette\DateTime('2013-02-03 17:00:00'),
				NULL,
				array()
			),
		);
	}



	/**
	 * @dataProvider dataProviderIsInDay
	 * @param bool $expected
	 * @param array $parameters
	 * @param \DateTime $now
	 */
	public function testDetectsAllowedDaysOfWeek($expected, array $parameters, DateTime $now)
	{
		$params = new Parameters($parameters);
		Assert::equal($expected, $params->isInDay($now));
	}



	public function dataProviderIsInDay()
	{
		return array(
			// One day
			array(TRUE, array(Parameters::DAYS => array('Mon',),), new Nette\DateTime('2013-02-11 12:34:56')),
			array(FALSE, array(Parameters::DAYS => array('Mon',),), new Nette\DateTime('2013-02-12 12:34:56')),
			array(TRUE, array(Parameters::DAYS => array('Tue',),), new Nette\DateTime('2013-02-12 12:34:56')),
			array(FALSE, array(Parameters::DAYS => array('Tue',),), new Nette\DateTime('2013-02-13 12:34:56')),
			array(TRUE, array(Parameters::DAYS => array('Wed',),), new Nette\DateTime('2013-02-13 12:34:56')),
			array(FALSE, array(Parameters::DAYS => array('Wed',),), new Nette\DateTime('2013-02-14 12:34:56')),
			array(TRUE, array(Parameters::DAYS => array('Thu',),), new Nette\DateTime('2013-02-14 12:34:56')),
			array(FALSE, array(Parameters::DAYS => array('Thu',),), new Nette\DateTime('2013-02-15 12:34:56')),
			array(TRUE, array(Parameters::DAYS => array('Fri',),), new Nette\DateTime('2013-02-15 12:34:56')),
			array(FALSE, array(Parameters::DAYS => array('Fri',),), new Nette\DateTime('2013-02-16 12:34:56')),
			array(TRUE, array(Parameters::DAYS => array('Sat',),), new Nette\DateTime('2013-02-16 12:34:56')),
			array(FALSE, array(Parameters::DAYS => array('Sat',),), new Nette\DateTime('2013-02-17 12:34:56')),
			array(TRUE, array(Parameters::DAYS => array('Sun',),), new Nette\DateTime('2013-02-17 12:34:56')),
			array(FALSE, array(Parameters::DAYS => array('Sun',),), new Nette\DateTime('2013-02-18 12:34:56')),
			// Empty days
			array(FALSE, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-11 12:34:56')),
			array(FALSE, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-12 12:34:56')),
			array(FALSE, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-13 12:34:56')),
			array(FALSE, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-14 12:34:56')),
			array(FALSE, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-15 12:34:56')),
			array(FALSE, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-16 12:34:56')),
			array(FALSE, array(Parameters::DAYS => array(),), new Nette\DateTime('2013-02-17 12:34:56')),
			// Without days
			array(TRUE, array(Parameters::DAYS => NULL,), new Nette\DateTime('2013-02-11 12:34:56')),
			array(TRUE, array(Parameters::DAYS => NULL,), new Nette\DateTime('2013-02-12 12:34:56')),
			array(TRUE, array(Parameters::DAYS => NULL,), new Nette\DateTime('2013-02-13 12:34:56')),
			array(TRUE, array(Parameters::DAYS => NULL,), new Nette\DateTime('2013-02-14 12:34:56')),
			array(TRUE, array(Parameters::DAYS => NULL,), new Nette\DateTime('2013-02-15 12:34:56')),
			array(TRUE, array(Parameters::DAYS => NULL,), new Nette\DateTime('2013-02-16 12:34:56')),
			array(TRUE, array(Parameters::DAYS => NULL,), new Nette\DateTime('2013-02-17 12:34:56')),
		);
	}



	/**
	 * @dataProvider dataProviderIsInTime
	 * @param bool $expected
	 * @param array $parameters
	 * @param string $now
	 */
	public function testDetectsAllowedTimeRange($expected, array $parameters, $now)
	{
		$now = new Nette\DateTime($now);
		$params = new Parameters($parameters);
		Assert::equal($expected, $params->isInTime($now));
	}



	public function dataProviderIsInTime()
	{
		return array(
			// One minute
			array(
				TRUE,
				array(Parameters::TIME => array(
					array(
						'from' => '11:00',
						'to' => NULL,
					),
				),
				),
				'2013-02-11 11:00:00'
			),
			array(
				TRUE,
				array(Parameters::TIME => array(
					array(
						'from' => '11:00',
						'to' => NULL,
					),
				),
				),
				'2013-02-11 11:00:59'
			),
			array(
				FALSE,
				array(Parameters::TIME => array(
					array(
						'from' => '11:00',
						'to' => NULL,
					),
				),
				),
				'2013-02-11 10:59:59'
			),
			array(
				FALSE,
				array(Parameters::TIME => array(
					array(
						'from' => '11:00',
						'to' => NULL,
					),
				),
				),
				'2013-02-11 11:01:00'
			),
			// Range
			array(
				TRUE,
				array(Parameters::TIME => array(
					array(
						'from' => '11:00',
						'to' => '12:00',
					),
				),
				),
				'2013-02-11 11:00:00'
			),
			array(
				TRUE,
				array(Parameters::TIME => array(
					array(
						'from' => '11:00',
						'to' => '12:00',
					),
				),
				),
				'2013-02-11 11:30:00'
			),
			array(
				TRUE,
				array(Parameters::TIME => array(
					array('from' => '11:00', 'to' => '12:00',),
				),
				),
				'2013-02-11 12:00:59'
			),
			array(
				FALSE,
				array(Parameters::TIME => array(
					array('from' => '11:00', 'to' => '12:00',),
				),
				),
				'2013-02-11 10:59:59'
			),
			array(
				FALSE,
				array(Parameters::TIME => array(
					array('from' => '11:00', 'to' => '12:00',),
				),
				),
				'2013-02-11 12:01:00'
			),
			// Empty
			array(
				TRUE,
				array(Parameters::TIME => array(),),
				'2013-02-11 12:00:00'
			),
			array(
				TRUE,
				array(Parameters::TIME => NULL,),
				'2013-02-11 12:00:00'
			),
		);
	}

}

run(new ParametersTest());
