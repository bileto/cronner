<?php

declare(strict_types=1);

namespace CronnerTests\Tasks;

require_once(__DIR__ . "/../bootstrap.php");

use Exception;
use Mockery;
use Nette\Reflection\ClassType;
use Nette\Reflection\Method;
use Bileto\Cronner\ITimestampStorage;
use Bileto\Cronner\Tasks\Task;
use CronnerTests\TestObjects\TestObject;
use Nette\Utils\DateTime;
use Tester\Assert;
use Tester\TestCase;

class TaskTest extends TestCase
{

    /** @var object */
    private $object;

    protected function setUp()
    {
        $this->object = new TestObject();
    }

    protected function tearDown()
    {
        Mockery::close();
    }

    public function testInvokesTaskWithSavingLastRunTime()
    {
        $now = new DateTime();
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
     * @throws Exception
     */
    public function testChecksIfCanBeRun(bool $expected, int $loads, string $methodName, string $now, string $lastRunTime = null)
    {
        $now = new DateTime($now);
        $lastRunTime = $lastRunTime ? new DateTime($lastRunTime) : NULL;

        $method = (new ClassType($this->object))->getMethod($methodName);

        $timestampStorage = Mockery::mock(ITimestampStorage::class);
        $timestampStorage->shouldReceive("loadLastRunTime")->times($loads)->andReturn($lastRunTime);
        $timestampStorage->shouldReceive("setTaskName")->atLeast(1);

        $task = new Task($this->object, $method, $timestampStorage);
        Assert::same($expected, $task->shouldBeRun($now));
    }

    public function dataProviderShouldBeRun(): array
    {
        return [
            // Test 01
            [true, 1, 'test01', '2013-02-01 12:00:00', null],
            [true, 1, 'test01', '2013-02-01 12:10:00', '2013-02-01 12:00:00'],
            [false, 1, 'test01', '2013-02-01 12:04:00', '2013-02-01 12:00:00'],
            // Test 02
            [false, 0, 'test02', '2013-02-05 12:00:00', null],
            [false, 0, 'test02', '2013-02-04 12:00:00', null],
            [false, 1, 'test02', '2013-02-04 09:30:00', '2013-02-04 09:00:00'],
            [true, 1, 'test02', '2013-02-04 09:30:00', null],
            [true, 1, 'test02', '2013-02-04 09:30:00', '2013-02-03 15:30:00'],
        ];
    }

    public function testShouldBeRunOnShortLaterRun()
    {
        $timestampStorage = Mockery::mock(ITimestampStorage::class);
        $timestampStorage->shouldReceive("loadLastRunTime")->once()->andReturn(new DateTime('2014-08-15 09:00:01'));
        $timestampStorage->shouldReceive("setTaskName")->atLeast(1);

        $method = new Method($this->object, 'test03');
        $task = new Task($this->object, $method, $timestampStorage);
        Assert::true($task->shouldBeRun(new DateTime('2014-08-15 09:17:00')));
    }

}

(new TaskTest())->run();
