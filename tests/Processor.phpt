<?php

namespace stekycz\Cronner\tests;

use Exception;
use Nette\DateTime;
use stekycz\Cronner\Processor;
use stekycz\Cronner\tests\objects\TestExceptionObject;
use stekycz\Cronner\tests\objects\TestObject;
use Tester\Assert;



require_once(__DIR__ . "/bootstrap.php");
require_once(__DIR__ . "/objects/TestObject.php");
require_once(__DIR__ . "/objects/TestExceptionObject.php");

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class ProcessorTest extends \TestCase
{

	/**
	 * @var \stekycz\Cronner\Processor
	 */
	private $processor;

	/**
	 * @var \stekycz\Cronner\ITimestampStorage
	 */
	private $timestampStorage;



	protected function setUp()
	{
		parent::setUp();
		$this->timestampStorage = $this->mockista->create(
			'\stekycz\Cronner\ITimestampStorage',
			array('setTaskName', 'saveRunTime', 'loadLastRunTime',)
		);
		$this->timestampStorage->expects('loadLastRunTime')
			->andReturn(new DateTime('2013-02-04 08:00:00'));
		$this->processor = new Processor($this->timestampStorage);
	}



	public function testAcceptsTasksObjectWithTaskMethods()
	{
		$tasks = $this->mockista->create('\stdClass');
		$this->timestampStorage->expects('setTaskName')
			->with('Test task');

		$this->processor->addTasks($tasks);
		Assert::equal(1, $this->processor->countTaskObjects());
	}



	/**
	 * @throws \stekycz\Cronner\InvalidArgumentException
	 */
	public function testThrowsExceptionOnDuplicateTasksObjectAddition()
	{
		$tasks = $this->mockista->create('\stdClass');
		$this->timestampStorage->expects('setTaskName')
			->with('Test task');

		$this->processor->addTasks($tasks);
		$this->processor->addTasks($tasks);
	}



	public function testProcessesAllAddedTasks()
	{
		$now = new DateTime('2013-02-04 09:30:00');
		$tasks = new TestObject();

		$this->timestampStorage->expects('setTaskName')
			->atLeast(8);
		$this->timestampStorage->expects('saveRunTime')
			->atLeastOnce();

		$this->processor->addTasks($tasks);
		Assert::equal(4, $this->processor->countTasks());
		$this->processor->process($now);
	}



	public function testCanSetLogCallback()
	{
		$this->processor->setLogCallback(function (Exception $e) {
			// This method is dummy
		});
	}



	public function testIsAbleToContinueWithNextTaskWhenOneTaskThrowException()
	{
		$logCallback = $this->mockista->create('\Nette\Object', array('log'));
		$logCallback->expects('log')
			->once()
			->andCallback(function (Exception $e) {
				Assert::true($e instanceof Exception);
				Assert::equal('Test 01', $e->getMessage());
			});

		$this->timestampStorage->expects('setTaskName');
		$this->timestampStorage->expects('saveRunTime');

		$this->processor->setLogCallback(function (Exception $e) use ($logCallback) {
			$logCallback->log($e);
		});
		$this->processor->addTasks(new TestExceptionObject());
		$this->processor->process();
	}

}

run(new ProcessorTest());
