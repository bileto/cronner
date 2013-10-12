<?php

namespace stekycz\Cronner\tests;

use stdClass;
use stekycz\Cronner\Cronner;
use Tester\Assert;



require_once(__DIR__ . "/bootstrap.php");

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class CronnerTest extends \TestCase
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
		$this->timestampStorage = $this->mockista->create(
			'\stekycz\Cronner\ITimestampStorage',
			array('setTaskName', 'saveRunTime', 'loadLastRunTime')
		);
		$this->cronner = new Cronner($this->timestampStorage);
	}



	/**
	 * @dataProvider dataProviderSetMaxExecutionTime
	 */
	public function testCanSetMaxExecutionTime($expected, $value)
	{
		$this->cronner->setMaxExecutionTime($value);
		Assert::same($expected, $this->cronner->getMaxExecutionTime());
	}



	public function dataProviderSetMaxExecutionTime()
	{
		return array(
			array(1234, 1234),
			array(1234, '1234'),
			array(1234, 1234.5),
			array(1234, '1234.5'),
			array(NULL, NULL),
		);
	}



	/**
	 * @dataProvider dataProviderSetMaxExecutionTimeError
	 * @throws \stekycz\Cronner\InvalidArgumentException
	 */
	public function testThrowsExceptionOnWrongTypeOfMaxExecutionTime($value)
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
			array(TRUE),
			array(FALSE),
			array(new stdClass()),
		);
	}

}

run(new CronnerTest());
