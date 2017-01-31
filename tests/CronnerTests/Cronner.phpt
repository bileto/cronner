<?php

/**
 * @testCase
 */

namespace stekycz\Cronner\tests;

use Exception;
use Mockery;
use Nette\Utils\DateTime;
use stdClass;
use stekycz\Cronner\Cronner;
use stekycz\Cronner\Exceptions\InvalidArgumentException;
use stekycz\Cronner\ITimestampStorage;
use stekycz\Cronner\Tasks\Task;
use stekycz\Cronner\tests\objects\AnotherSimpleTestObject;
use stekycz\Cronner\tests\objects\NextSimpleTestObject;
use stekycz\Cronner\tests\objects\SameTaskNameObject;
use stekycz\Cronner\tests\objects\TestExceptionObject;
use stekycz\Cronner\tests\objects\TestObject;
use Tester\Assert;

require_once(__DIR__ . "/bootstrap.php");

class CronnerTest extends \TestCase
{

	/**
	 * @var Cronner
	 */
	private $cronner;

	/**
	 * @var ITimestampStorage
	 */
	private $timestampStorage;

	protected function setUp()
	{
		parent::setUp();
		$timestampStorage = Mockery::mock('\stekycz\Cronner\ITimestampStorage');
		$timestampStorage->shouldReceive('setTaskName');
		$timestampStorage->shouldReceive('saveRunTime');
		$timestampStorage->shouldReceive('loadLastRunTime')->andReturn(new DateTime('2013-02-04 08:00:00'));
		$this->timestampStorage = $timestampStorage;

		$criticalSection = Mockery::mock('\stekycz\Cronner\CriticalSection');
		$criticalSection->shouldReceive("enter")->andReturn(TRUE);
		$criticalSection->shouldReceive("leave")->andReturn(TRUE);
		$criticalSection->shouldReceive("isEntered")->andReturn(FALSE);

		$this->cronner = new Cronner($this->timestampStorage, $criticalSection);
		$this->cronner->onTaskBegin = array();
		$this->cronner->onTaskFinished = array();
		$this->cronner->onTaskError = array();
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
	 * @throws InvalidArgumentException
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
		$this->cronner->addTasks(new \stdClass());
		Assert::equal(1, $this->cronner->countTaskObjects());
	}

	/**
	 * @throws \stekycz\Cronner\Exceptions\InvalidArgumentException
	 */
	public function testThrowsExceptionOnDuplicateTasksObjectAddition()
	{
		$tasks = new \stdClass();
		$this->cronner->addTasks($tasks);
		$this->cronner->addTasks($tasks);
	}

	public function testProcessesAllAddedTasks()
	{
		$now = new DateTime('2013-02-04 09:30:00');
		$tasks = new TestObject();

		$this->timestampStorage->shouldReceive('setTaskName')->atLeast(8);
		$this->timestampStorage->shouldReceive('saveRunTime')->with($now)->atLeast(1);

		$this->cronner->addTasks($tasks);
		Assert::equal(4, $this->cronner->countTasks());
		$this->cronner->run($now);
	}

	public function testCanSetOnTaskBeginCallback()
	{
		$this->cronner->onTaskBegin[] = function (Cronner $cronner, Task $task) {
			// This method is dummy
		};
		Assert::equal(1, count($this->cronner->onTaskBegin));
	}

	public function testCanSetOnTaskFinishedCallback()
	{
		$this->cronner->onTaskFinished[] = function (Cronner $cronner, Task $task) {
			// This method is dummy
		};
		Assert::equal(1, count($this->cronner->onTaskFinished));
	}

	public function testCanSetOnTaskErrorCallback()
	{
		$this->cronner->onTaskError[] = function (Cronner $cronner, Exception $e, Task $task) {
			// This method is dummy
		};
		Assert::equal(1, count($this->cronner->onTaskError));
	}

	public function testIsAbleToContinueWithNextTaskWhenOneTaskThrowException()
	{
		$now = new DateTime('2013-02-04 09:30:00');

		$logCallback = Mockery::mock('\Nette\Object');
		$logCallback->shouldReceive('logError')->once()->andReturnUsing(function (Exception $e, Task $task) {
			Assert::equal('Test 01', $e->getMessage());
		});
		$logCallback->shouldReceive('logBegin')->twice();
		$logCallback->shouldReceive('logFinished')->once();

		$this->timestampStorage->shouldReceive('setTaskName');
		$this->timestampStorage->shouldReceive('saveRunTime');

		$this->cronner->onTaskBegin[] = function (Cronner $cronner, Task $task) use ($logCallback) {
			$logCallback->logBegin($task);
		};
		$this->cronner->onTaskFinished[] = function (Cronner $cronner, Task $task) use ($logCallback) {
			$logCallback->logFinished($task);
		};
		$this->cronner->onTaskError[] = function (Cronner $cronner, Exception $e, Task $task) use ($logCallback) {
			$logCallback->logError($e, $task);
		};
		$this->cronner->addTasks(new TestExceptionObject());
		$this->cronner->run($now);
	}

	public function testAddingTwoTestsWithTheSameNameInOneObject()
	{
		$cronner = $this->cronner;
		Assert::exception(function () use ($cronner) {
			$cronner->addTasks(new SameTaskNameObject());
		}, '\stekycz\Cronner\Exceptions\DuplicateTaskNameException');
	}

	public function testAddingTwoTestsWithTheSameNameInMoreObjects()
	{
		$cronner = $this->cronner;
		Assert::exception(function () use ($cronner) {
			$cronner->addTasks(new AnotherSimpleTestObject());
			$cronner->addTasks(new NextSimpleTestObject());
		}, '\stekycz\Cronner\Exceptions\DuplicateTaskNameException');
	}

}

run(new CronnerTest());
