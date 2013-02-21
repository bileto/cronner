<?php

namespace stekycz\Cronner\tests;

require_once(TEST_DIR . '/objects/TestObject.php');

use PHPUnit_Framework_TestCase;
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

	protected function setUp() {
		parent::setUp();
		$timestampStorage = $this->getMock(
			'\stekycz\Cronner\ITimestampStorage',
			array('saveRunTime', 'loadLastRunTime', )
		);
		$timestampStorage->expects($this->any())
			->method('loadLastRunTime')
			->will($this->returnValue(new DateTime('2013-02-04 08:00:00')));
		$this->processor = new Processor($timestampStorage);
	}

	/**
	 * @test
	 */
	public function acceptsTasksObjectWithTaskMethods() {
		$tasks = $this->getMock(
			'\stekycz\Cronner\Tasks',
			array('getName')
		);

		$this->processor->addTasks($tasks);
		$this->assertEquals(1, $this->processor->countTaskObjects());
	}

	/**
	 * @test
	 * @expectedException \stekycz\Cronner\InvalidArgumentException
	 */
	public function throwsExceptionOnDuplicateTasksObjectAddition() {
		$tasks = $this->getMock(
			'\stekycz\Cronner\Tasks',
			array('getName')
		);
		$tasks->expects($this->exactly(4))
			->method('getName')
			->will($this->returnValue('test'));

		$this->processor->addTasks($tasks);
		$this->processor->addTasks($tasks);
	}

	/**
	 * @test
	 */
	public function processesAllAddedTasks() {
		$now = new DateTime('2013-02-04 09:30:00');
		$tasks = $this->getMock(
			'\stekycz\Cronner\tests\objects\TestObject',
			array('getName', 'test01', 'test02', 'test03', 'test04', )
		);

		$this->processor->addTasks($tasks);
		$this->processor->process($now);
	}

}
