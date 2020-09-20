<?php

declare(strict_types=1);

namespace CronnerTests\TimestampStorage;

require_once(__DIR__ . "/../bootstrap.php");

use Bileto\Cronner\Exceptions\InvalidTaskNameException;
use DateTime;
use Mockery;
use Nette\Utils\DateTime as NetteDateTime;
use Nette\Utils\FileSystem;
use Bileto\Cronner\TimestampStorage\FileStorage;
use Tester\Assert;
use Tester\TestCase;

class FileStorageTest extends TestCase
{

    /** @var FileStorage */
    private $storage;

    protected function setUp()
    {
        FileSystem::createDir(static::getTempDirPath());
        $this->storage = new FileStorage(static::getTempDirPath());
    }

    protected function tearDown()
    {
        FileSystem::delete(static::getTempDirPath());
        Mockery::close();
    }

    private static function getTempDirPath()
    {
        return TEMP_DIR . '/cronner';
    }

    public function testIsAbleToSetTaskName()
    {
        $this->storage->setTaskName('Test task 1');
        $this->storage->setTaskName(null);
        $this->storage->setTaskName();
        Assert::$counter++; // Hack for nette tester
    }

    /**
     * @dataProvider dataProviderSetTaskName
     * @param string|null $taskName
     */
    public function testThrowsExceptionOnInvalidTaskName(string $taskName = NULL)
    {
        Assert::throws(function () use ($taskName) {
            $this->storage->setTaskName($taskName);
        }, InvalidTaskNameException::class);
    }

    public function dataProviderSetTaskName(): array
    {
        return [
            [''],
        ];
    }

    /**
     * Tests that saving do not throws any exception.
     *
     * @dataProvider dataProviderSaveRunTime
     * @param DateTime $date
     */
    public function testLoadsAndSavesLastRunTimeWithoutErrors(DateTime $date)
    {
        $this->storage->setTaskName('Test task 1');

        $lastRunTime = $this->storage->loadLastRunTime();
        Assert::null($lastRunTime);
        $this->storage->saveRunTime($date);
        $lastRunTime = $this->storage->loadLastRunTime();

        $this->storage->setTaskName();

        Assert::type('\DateTime', $lastRunTime);
        Assert::equal($date->format('Y-m-d H:i:s O'), $lastRunTime->format('Y-m-d H:i:s O'));
    }

    public function dataProviderSaveRunTime(): array
    {
        return [
            [new NetteDateTime('2013-01-30 17:30:00')],
            [new NetteDateTime('2013-01-30 18:30:01')],
            [new NetteDateTime('2013-01-30 18:31:01')],
        ];
    }

    public function testSavesLastRunTimeByTaskName()
    {
        $date = new DateTime('2013-01-30 17:30:00');

        $this->storage->setTaskName('Test task 1');
        $lastRunTime = $this->storage->loadLastRunTime();
        Assert::null($lastRunTime);
        $this->storage->saveRunTime($date);
        $lastRunTime = $this->storage->loadLastRunTime();

        $this->storage->setTaskName();

        Assert::type('\DateTime', $lastRunTime);
        Assert::same($date->format('Y-m-d H:i:s O'), $lastRunTime->format('Y-m-d H:i:s O'));

        $this->storage->setTaskName('Test task 2');
        $lastRunTime = $this->storage->loadLastRunTime();
        Assert::null($lastRunTime);
        $this->storage->saveRunTime($date);
        $lastRunTime = $this->storage->loadLastRunTime();

        $this->storage->setTaskName();

        Assert::type('\DateTime', $lastRunTime);
        Assert::equal($date->format('Y-m-d H:i:s O'), $lastRunTime->format('Y-m-d H:i:s O'));
    }

}

(new FileStorageTest())->run();
