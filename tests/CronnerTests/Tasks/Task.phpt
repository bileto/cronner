<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace stekycz\Cronner\tests\Tasks;

use Mockery;
use Nette;
use Nette\Reflection\Method;
use stekycz\Cronner\ITimestampStorage;
use stekycz\Cronner\Tasks\Task;
use stekycz\Cronner\tests\objects\TestObject;
use Tester\Assert;

require_once(__DIR__ . "/../bootstrap.php");

class TaskTest extends \TestCase
{

	/**
	 * @var object
	 */
	private $object;

	protected function setUp()
	{
		parent::setUp();
		$this->object = new TestObject();
	}

	public function testInvokesTaskWithSavingLastRunTime()
	{
		$now = new Nette\Utils\DateTime();
		$timestampStorage = Mockery::mock(ITimestampStorage::class);
		$timestampStorage->shouldReceive("saveRunTime")->with($now)->once();
		$timestampStorage->shouldReceive("setTaskName")->times(2);

		$method = new Method($this->object, 'test01');
		$task = new Task($this->object, $method, $timestampStorage);
		$task($now);
		Assert::$counter++; // Hack for nette tester
	}

	/**
	 * @dataProvider dataProviderShouldBeRun
	 * @param bool $expected
	 * @param int $loads
	 * @param string $methodName
	 * @param string $now
	 * @param string|null $lastRunTime
	 */
	public function testChecksIfCanBeRun(bool $expected, int $loads, string $methodName, string $now, string $lastRunTime = NULL)
	{
		$now = new Nette\Utils\DateTime($now);
		$lastRunTime = $lastRunTime ? new Nette\Utils\DateTime($lastRunTime) : NULL;

		$method = (new \Nette\Reflection\ClassType($this->object))->getMethod($methodName);

		$timestampStorage = Mockery::mock(ITimestampStorage::class);
		$timestampStorage->shouldReceive("loadLastRunTime")->times($loads)->andReturn($lastRunTime);
		$timestampStorage->shouldReceive("setTaskName")->atLeast(1);

		$task = new Task($this->object, $method, $timestampStorage);
		Assert::same($expected, $task->shouldBeRun($now));
	}

	public function dataProviderShouldBeRun() : array
	{
		return [
			// Test 01
			[TRUE, 1, 'test01', '2013-02-01 12:00:00', NULL],
			[TRUE, 1, 'test01', '2013-02-01 12:10:00', '2013-02-01 12:00:00'],
			[FALSE, 1, 'test01', '2013-02-01 12:04:00', '2013-02-01 12:00:00'],
			// Test 02
			[FALSE, 0, 'test02', '2013-02-05 12:00:00', NULL],
			[FALSE, 0, 'test02', '2013-02-04 12:00:00', NULL],
			[FALSE, 1, 'test02', '2013-02-04 09:30:00', '2013-02-04 09:00:00'],
			[TRUE, 1, 'test02', '2013-02-04 09:30:00', NULL],
			[TRUE, 1, 'test02', '2013-02-04 09:30:00', '2013-02-03 15:30:00'],
		];
	}

	public function testShouldBeRunOnShortLaterRun()
	{
		$timestampStorage = Mockery::mock(ITimestampStorage::class);
		$timestampStorage->shouldReceive("loadLastRunTime")->once()->andReturn(new Nette\Utils\DateTime('2014-08-15 09:00:01'));
		$timestampStorage->shouldReceive("setTaskName")->atLeast(1);

		$method = new Method($this->object, 'test03');
		$task = new Task($this->object, $method, $timestampStorage);
		Assert::true($task->shouldBeRun(new Nette\Utils\DateTime('2014-08-15 09:17:00')));
	}

	/**
	 * @dataProvider dataProviderNextRun
	 * @param string $methodName
	 * @param string $now
	 * @param string|null $lastRunTime
	 * @param string $nextRunTime
	 */
	public function testNextRun(string $methodName, string $now, string $lastRunTime = NULL, string $nextRunTime)
	{
		$now = new \DateTime($now);
		$nextRunTime = new \DateTime($nextRunTime);

		$method = (new \Nette\Reflection\ClassType($this->object))->getMethod($methodName);

		$timestampStorage = Mockery::mock(ITimestampStorage::class);
		$timestampStorage->shouldReceive("loadLastRunTime")->atLeast(1)->andReturnUsing(function() use ($lastRunTime) {
			return $lastRunTime ? \DateTime::createFromFormat('Y-m-d H:i:s', $lastRunTime) : NULL;
		});

		$timestampStorage->shouldReceive("setTaskName")->atLeast(1);

		$task = new Task($this->object, $method, $timestampStorage);

		Assert::equal($nextRunTime, $task->getNextRun($now));
		Assert::same(TRUE, $task->shouldBeRun($nextRunTime));
	}

	public function dataProviderNextRun() : array
	{
		return [
			// Test 01
			['test01', '2013-02-01 12:00:00', NULL, '2013-02-01 12:00:00'],
			['test01', '2013-02-01 12:00:00', '2013-02-01 11:59:59', '2013-02-01 12:04:59'],
			['test01', '2013-02-01 12:00:00', '2013-02-01 12:00:00', '2013-02-01 12:05:00'],
			['test01', '2013-02-01 12:10:00', '2013-02-01 12:06:55', '2013-02-01 12:11:55'],
			['test01', '2013-02-02 15:20:23', '2012-02-01 12:06:55', '2013-02-02 15:20:23'],
			// Test 02
			['test02', '2013-02-01 12:00:00', NULL, '2013-02-01 15:00:00'],
			['test02', '2013-02-01 09:00:00', NULL, '2013-02-01 09:00:00'],
			['test02', '2013-02-01 09:00:38', '2013-02-01 09:00:00', '2013-02-01 10:00:00'],
			['test02', '2013-02-01 09:01:38', '2013-02-01 09:00:01', '2013-02-01 15:00:00'],
			['test02', '2013-02-01 14:01:38', NULL, '2013-02-01 15:00:00'],
			['test02', '2013-02-01 14:01:38', '2013-02-01 09:00:01', '2013-02-01 15:00:00'],
			['test02', '2013-02-01 15:01:38', '2013-02-01 09:00:01', '2013-02-01 15:01:38'],
			['test02', '2013-02-01 15:01:38', '2013-02-01 15:00:00', '2013-02-01 16:00:00'],
			['test02', '2013-02-01 15:01:38', '2013-02-01 15:00:01', '2013-02-04 09:00:00'],
			['test02', '2013-02-04 14:01:38', '2013-02-04 09:00:01', '2013-02-04 15:00:00'],
			['test02', '2013-02-04 15:01:38', '2013-02-04 09:00:01', '2013-02-04 15:01:38'],
			['test02', '2013-02-04 15:01:38', '2013-02-04 15:00:00', '2013-02-04 16:00:00'],
			['test02', '2013-02-04 15:01:38', '2013-02-04 15:00:01', '2013-02-06 09:00:00'],
			['test02', '2013-02-04 15:01:38', NULL, '2013-02-04 15:01:38'],
			['test02', '2013-02-05 14:01:38', '2013-02-04 09:00:01', '2013-02-06 09:00:00'],
			['test02', '2013-02-05 06:01:38', '2013-02-04 09:00:01', '2013-02-06 09:00:00'],
			['test02', '2013-02-05 9:00:00', '2013-02-04 15:00:00', '2013-02-06 09:00:00'],
			['test02', '2013-02-05 15:01:38', '2013-02-04 15:00:01', '2013-02-06 09:00:00'],
			['test02', '2013-02-05 15:01:38', NULL, '2013-02-06 09:00:00'],
			['test02', '2013-02-05 9:00:00', NULL, '2013-02-06 09:00:00'],
			// Test 03
			['test03', '2013-02-01 09:00:00', NULL, '2013-02-01 09:00:00'],
			['test03', '2013-02-01 09:00:38', '2013-02-01 09:00:00', '2013-02-01 09:17:00'],
			['test03', '2013-02-01 09:01:38', '2013-02-01 09:00:01', '2013-02-01 09:17:01'],
			['test03', '2013-02-01 10:44:38', '2013-02-01 09:00:01', '2013-02-01 10:44:38'],
			['test03', '2013-02-01 10:45:00', '2013-02-01 09:00:01', '2013-02-01 10:45:00'],
			['test03', '2013-02-01 10:45:01', '2013-02-01 09:00:01', '2013-02-04 09:00:00'],
			['test03', '2013-02-01 12:00:00', NULL, '2013-02-04 09:00:00'],
			['test03', '2013-02-01 12:00:00', '2013-02-01 09:00:01', '2013-02-04 09:00:00'],
			['test03', '2013-02-03 09:00:00', '2013-02-01 09:00:01', '2013-02-04 09:00:00'],
			['test03', '2013-02-05 09:01:38', NULL, '2013-02-05 09:01:38'],
			['test03', '2013-02-05 09:01:38', '2013-02-01 09:00:01', '2013-02-05 09:01:38'],
			['test03', '2013-02-05 09:18:38', '2013-02-05 09:00:00', '2013-02-05 09:18:38'],
			['test03', '2013-02-05 10:45:00', NULL, '2013-02-05 10:45:00'],
			['test03', '2013-02-05 10:45:01', NULL, '2013-02-06 09:00:00'],
			['test03', '2013-02-05 10:45:00', '2013-02-05 10:28:59', '2013-02-06 09:00:00'],
			['test03', '2013-02-05 10:45:00', '2013-02-05 10:28:00', '2013-02-05 10:45:00'],
			['test03', '2013-02-05 10:45:00', '2013-02-05 10:28:05', '2013-02-06 09:00:00'],
			// Test 04
			['test04', '2013-02-01 09:00:00', NULL, '2013-02-02 00:00:00'],
			['test04', '2013-02-01 09:00:38', '2013-02-01 09:00:00', '2013-02-02 09:00:00'],
			['test04', '2013-02-01 09:00:38', '2013-02-01 09:00:38', '2013-02-02 09:00:38'],
			['test04', '2013-02-01 09:00:38', '2012-02-01 09:00:38', '2013-02-02 00:00:00'],
			['test04', '2013-02-02 09:01:38', NULL, '2013-02-02 09:01:38'],
			['test04', '2013-02-02 09:01:38', '2013-02-01 00:01:38', '2013-02-02 09:01:38'],
			['test04', '2013-02-02 00:01:38', '2013-02-01 00:02:38', '2013-02-02 00:02:38'],
			['test04', '2013-02-02 09:01:38', '2013-02-02 00:01:38', '2013-02-03 00:01:38'],
			['test04', '2013-02-03 00:01:38', '2013-02-02 00:01:38', '2013-02-03 00:01:38'],
			['test04', '2013-02-03 00:01:37', '2013-02-02 00:01:38', '2013-02-03 00:01:38'],
			['test04', '2013-02-03 10:44:38', '2013-02-03 00:00:01', '2013-02-09 00:00:00'],
			['test04', '2013-02-03 10:44:38', '2013-02-03 00:26:01', '2013-02-09 00:00:00'],
			['test04', '2013-02-04 00:00:00', NULL, '2013-02-09 00:00:00'],
			['test04', '2013-02-06 23:46:27', NULL, '2013-02-09 00:00:00'],
		];
	}

}

run(new TaskTest());
