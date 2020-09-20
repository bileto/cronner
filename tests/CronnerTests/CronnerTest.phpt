<?php

declare(strict_types=1);

namespace CronnerTests;

require_once(__DIR__ . "/bootstrap.php");

use Exception;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Nette\Utils\DateTime;
use stdClass;
use Bileto\CriticalSection\ICriticalSection;
use Bileto\Cronner\Cronner;
use Bileto\Cronner\Exceptions\DuplicateTaskNameException;
use Bileto\Cronner\ITimestampStorage;
use Bileto\Cronner\Tasks\Task;
use CronnerTests\Objects\AnotherSimpleTestObject;
use CronnerTests\Objects\NextSimpleTestObject;
use CronnerTests\Objects\SameTaskNameObject;
use CronnerTests\Objects\TestExceptionObject;
use CronnerTests\Objects\TestObject;
use Tester\Assert;
use Tester\TestCase;
use TypeError;

class CronnerTest extends TestCase
{

    /** @var Cronner */
    private $cronner;

    /** @var ITimestampStorage|MockInterface */
    private $timestampStorage;

    protected function setUp()
    {
        $timestampStorage = Mockery::mock(ITimestampStorage::class);
        $timestampStorage->shouldReceive('setTaskName');
        $timestampStorage->shouldReceive('saveRunTime');
        $timestampStorage->shouldReceive('loadLastRunTime')->andReturn(new DateTime('2013-02-04 08:00:00'));
        $this->timestampStorage = $timestampStorage;

        $criticalSection = Mockery::mock(ICriticalSection::class);
        $criticalSection->shouldReceive("enter")->andReturn(true);
        $criticalSection->shouldReceive("leave")->andReturn(true);
        $criticalSection->shouldReceive("isEntered")->andReturn(false);

        $this->cronner = new Cronner($this->timestampStorage, $criticalSection);
        $this->cronner->onTaskBegin = [];
        $this->cronner->onTaskFinished = [];
        $this->cronner->onTaskError = [];
    }

    protected function tearDown()
    {
        Mockery::close();
    }

    /**
     * @dataProvider dataProviderSetMaxExecutionTime
     * @param int|null $expected
     * @param int|null $value
     */
    public function testCanSetMaxExecutionTime(int $expected = null, int $value = null)
    {
        $this->cronner->setMaxExecutionTime($value);
        Assert::same($expected, $this->cronner->getMaxExecutionTime());
    }

    public function dataProviderSetMaxExecutionTime(): array
    {
        return [
            [1234, 1234],
            [null, null],
        ];
    }

    /**
     * @dataProvider dataProviderSetMaxExecutionTimeError
     * @param $value
     */
    public function testThrowsExceptionOnWrongTypeOfMaxExecutionTime($value)
    {
        Assert::throws(function () use ($value) {
            $this->cronner->setMaxExecutionTime($value);
        }, InvalidArgumentException::class);
    }

    public function dataProviderSetMaxExecutionTimeError()
    {
        return [
            ['-1234'],
            ['0'],
            [-2.5],
            ['-2.5'],
            [0.0],
            ['0.0'],
            ['nejaky blabol'],
            [true],
            [false],
            [new stdClass()],
        ];
    }

    /**
     * @dataProvider dataProviderSetMaxExecutionTimeWrongValue
     * @param $value
     */
    public function testThrowsExceptionOnWrongValueOfMaxExecutionTime($value)
    {
        Assert::throws(function () use ($value) {
            $this->cronner->setMaxExecutionTime($value);
        }, InvalidArgumentException::class);
    }

    public function dataProviderSetMaxExecutionTimeWrongValue()
    {
        return [
            [-1234],
            [0],
        ];
    }

    public function testAcceptsTasksObjectWithTaskMethods()
    {
        $this->cronner->addTasks(new stdClass());
        Assert::equal(1, $this->cronner->countTaskObjects());
    }

    public function testThrowsExceptionOnDuplicateTasksObjectAddition()
    {
        $tasks = new stdClass();
        $this->cronner->addTasks($tasks);

        Assert::throws(function () use ($tasks) {
            $this->cronner->addTasks($tasks);
        }, InvalidArgumentException::class);
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

        $logCallback = Mockery::mock(stdClass::class);
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
        }, DuplicateTaskNameException::class);
    }

    public function testAddingTwoTestsWithTheSameNameInMoreObjects()
    {
        $cronner = $this->cronner;
        Assert::exception(function () use ($cronner) {
            $cronner->addTasks(new AnotherSimpleTestObject());
            $cronner->addTasks(new NextSimpleTestObject());
        }, DuplicateTaskNameException::class);
    }

}

(new CronnerTest())->run();
