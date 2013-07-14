<?php

namespace stekycz\Cronner\tests\Tasks;

use Nette\Reflection\Method;
use Nette;
use PHPUnit_Framework_TestCase;
use stekycz\Cronner\Tasks\Task;
use stekycz\Cronner\tests\objects\TestObject;

require_once(TEST_DIR . '/objects/TestObject.php');

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-21
 */
class Task_Test extends PHPUnit_Framework_TestCase
{

	/**
	 * @var \stekycz\Cronner\ITasksContainer
	 */
	private $object;

	protected function setUp()
	{
		parent::setUp();
		$this->object = new TestObject();
	}

	/**
	 * @test
	 */
	public function invokesTaskWithSavingLastRunTime()
	{
		$timestampStorage = $this->getMock('\stekycz\Cronner\ITimestampStorage');
		$timestampStorage->expects($this->once())
			->method('saveRunTime');

		$method = new Method($this->object, 'test01');
		$task = new Task($this->object, $method, $timestampStorage);
		$task();
	}

	/**
	 * @test
	 * @dataProvider dataProviderShouldBeRun
	 * @param bool $expected
	 * @param int $loads
	 * @param string $methodName
	 * @param string $now
	 * @param string $lastRunTime
	 */
	public function checksIfCanBeRun($expected, $loads, $methodName, $now, $lastRunTime)
	{
		$now = new Nette\DateTime($now);
		$lastRunTime = $lastRunTime ? new Nette\DateTime($lastRunTime) : null;

		$method = $this->object->getReflection()->getMethod($methodName);

		$timestampStorage = $this->getMock(
			'\stekycz\Cronner\ITimestampStorage',
			array('setTaskName', 'saveRunTime', 'loadLastRunTime',)
		);
		$timestampStorage->expects($this->exactly($loads))
			->method('loadLastRunTime')
			->will($this->returnValue($lastRunTime));
		$timestampStorage->expects($this->any())
			->method('setTaskName')
			->withAnyParameters();

		$task = new Task($this->object, $method, $timestampStorage);
		$this->assertEquals($expected, $task->shouldBeRun($now));
	}

	public function dataProviderShouldBeRun()
	{
		return array(
			// Test 01
			array(true, 1, 'test01', '2013-02-01 12:00:00', null),
			array(true, 1, 'test01', '2013-02-01 12:10:00', '2013-02-01 12:00:00'),
			array(false, 1, 'test01', '2013-02-01 12:04:00', '2013-02-01 12:00:00'),
			// Test 02
			array(false, 0, 'test02', '2013-02-05 12:00:00', null),
			array(false, 0, 'test02', '2013-02-04 12:00:00', null),
			array(false, 1, 'test02', '2013-02-04 09:30:00', '2013-02-04 09:00:00'),
			array(true, 1, 'test02', '2013-02-04 09:30:00', null),
			array(true, 1, 'test02', '2013-02-04 09:30:00', '2013-02-03 15:30:00'),
		);
	}

}
