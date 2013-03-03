<?php

namespace stekycz\Cronner\tests;

require_once(TEST_DIR . '/objects/TestObject.php');
require_once(TEST_DIR . '/objects/TestExceptionObject.php');

use PHPUnit_Framework_TestCase;
use stekycz\Cronner\tests\objects\TestExceptionObject;
use Exception;
use stekycz\Cronner\tests\objects\TestObject;
use Nette\DateTime;
use stekycz\Cronner\Processor;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-21
 */
class Processor_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @var \stekycz\Cronner\Processor
	 */
	private $processor;

	/**
	 * @var \stekycz\Cronner\ITimestampStorage
	 */
	private $timestampStorage;

	protected function setUp() {
		parent::setUp();
		$this->timestampStorage = $this->getMock(
			'\stekycz\Cronner\ITimestampStorage',
			array('setTaskName', 'saveRunTime', 'loadLastRunTime', )
		);
		$this->timestampStorage->expects($this->any())
			->method('loadLastRunTime')
			->will($this->returnValue(new DateTime('2013-02-04 08:00:00')));
		$this->processor = new Processor($this->timestampStorage);
	}

	/**
	 * @test
	 */
	public function acceptsTasksObjectWithTaskMethods() {
		$tasks = $this->getMock('\stekycz\Cronner\ITasksContainer');
		$this->timestampStorage->expects($this->any())
			->method('setTaskName')
			->with('Test task');

		$this->processor->addTasks($tasks);
		$this->assertEquals(1, $this->processor->countTaskObjects());
	}

	/**
	 * @test
	 * @expectedException \stekycz\Cronner\InvalidArgumentException
	 */
	public function throwsExceptionOnDuplicateTasksObjectAddition() {
		$tasks = $this->getMock('\stekycz\Cronner\ITasksContainer');
		$this->timestampStorage->expects($this->any())
			->method('setTaskName')
			->with('Test task');

		$this->processor->addTasks($tasks);
		$this->processor->addTasks($tasks);
	}

	/**
	 * @test
	 */
	public function processesAllAddedTasks() {
		$now = new DateTime('2013-02-04 09:30:00');
		$tasks = new TestObject();

		$this->processor->addTasks($tasks);
		$this->processor->process($now);
	}

	/**
	 * @test
	 */
	public function canSetLogCallback() {
		$this->processor->setLogCallback(function (Exception $e) {
			// This method is dummy
		});
	}

	/**
	 * @test
	 */
	public function isAbleToContinueWithNextTaskWhenOneTaskThrowException() {
		$logCallback = $this->getMock('\Nette\Object', array('log'));
		$logCallback->expects($this->once())
			->method('log')
			->with(new Exception('Test 01'));

		$this->processor->setLogCallback(callback($logCallback, 'log'));
		$this->processor->addTasks(new TestExceptionObject());
		$this->processor->process();
	}

}
