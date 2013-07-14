<?php

namespace stekycz\Cronner\tests;

use PHPUnit_Framework_TestCase;
use stdClass;
use stekycz\Cronner\Cronner;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-03-03
 */
class Cronner_Test extends PHPUnit_Framework_TestCase
{

	/**
	 * @var \stekycz\Cronner\Cronner
	 */
	private $cronner;

	/**
	 * @var \stekycz\Cronner\ITimestampStorage
	 */
	private $timestampStorage;

	protected function setUp()
	{
		parent::setUp();
		$this->timestampStorage = $this->getMock(
			'\stekycz\Cronner\ITimestampStorage',
			array('setTaskName', 'saveRunTime', 'loadLastRunTime',)
		);
		$this->cronner = new Cronner($this->timestampStorage);
	}

	/**
	 * @test
	 * @dataProvider dataProviderSetMaxExecutionTime
	 */
	public function canSetMaxExecutionTime($expected, $value)
	{
		$this->cronner->setMaxExecutionTime($value);
		$this->assertEquals($expected, $this->cronner->getMaxExecutionTime());
	}

	public function dataProviderSetMaxExecutionTime()
	{
		return array(
			array(1234, 1234),
			array(1234, '1234'),
			array(1234, 1234.5),
			array(1234, '1234.5'),
			array(null, null),
		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderSetMaxExecutionTimeError
	 * @expectedException \stekycz\Cronner\InvalidArgumentException
	 */
	public function throwsExceptionOnWrongTypeOfMaxExecutionTime($value)
	{
		$this->cronner->setMaxExecutionTime($value);
	}

	public function dataProviderSetMaxExecutionTimeError()
	{
		return array(
			array(-1234),
			array('-1234'),
			array(0),
			array('0'),
			array(-2.5),
			array('-2.5'),
			array(0.0),
			array('0.0'),
			array('nejaky blabol'),
			array(true),
			array(false),
			array(new stdClass()),
		);
	}

}
