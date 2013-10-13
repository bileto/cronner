<?php

namespace stekycz\Cronner\tests;

use Exception;
use Nette\DateTime;
use stdClass;
use stekycz\Cronner\Cronner;
use stekycz\Cronner\tests\objects\TestExceptionObject;
use stekycz\Cronner\tests\objects\TestObject;
use Tester\Assert;



require_once(__DIR__ . "/bootstrap.php");
require_once(__DIR__ . "/objects/TestObject.php");
require_once(__DIR__ . "/objects/TestExceptionObject.php");

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
		$this->timestampStorage->expects('loadLastRunTime')
			->andReturn(new DateTime('2013-02-04 08:00:00'));
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



	public function testAcceptsTasksObjectWithTaskMethods()
	{
		$tasks = $this->mockista->create('\stdClass');

		$this->cronner->addTasks($tasks);
		Assert::equal(1, $this->cronner->countTaskObjects());
	}



	/**
	 * @throws \stekycz\Cronner\InvalidArgumentException
	 */
	public function testThrowsExceptionOnDuplicateTasksObjectAddition()
	{
		$tasks = $this->mockista->create('\stdClass');

		$this->cronner->addTasks($tasks);
		$this->cronner->addTasks($tasks);
	}



	public function testProcessesAllAddedTasks()
	{
		$now = new DateTime('2013-02-04 09:30:00');
		$tasks = new TestObject();

		$this->timestampStorage->expects('setTaskName')
			->atLeast(8);
		$this->timestampStorage->expects('saveRunTime')
			->atLeastOnce();

		$this->cronner->addTasks($tasks);
		Assert::equal(4, $this->cronner->countTasks());
		$this->cronner->run($now);
	}



	public function testCanSetLogCallback()
	{
		$this->cronner->setLogCallback(function (Exception $e) {
			// This method is dummy
		});
	}



	public function testIsAbleToContinueWithNextTaskWhenOneTaskThrowException()
	{
		$now = new DateTime('2013-02-04 09:30:00');

		$logCallback = $this->mockista->create('\Nette\Object', array('log'));
		$logCallback->expects('log')
			->once()
			->andCallback(function (Exception $e) {
				Assert::true($e instanceof Exception);
				Assert::equal('Test 01', $e->getMessage());
			});

		$this->timestampStorage->expects('setTaskName');
		$this->timestampStorage->expects('saveRunTime');

		$this->cronner->setLogCallback(function (Exception $e) use ($logCallback) {
			$logCallback->log($e);
		});
		$this->cronner->addTasks(new TestExceptionObject());
		$this->cronner->run($now);
	}

}

run(new CronnerTest());
