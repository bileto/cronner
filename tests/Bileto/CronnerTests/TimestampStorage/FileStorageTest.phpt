<?php

declare(strict_types=1);

namespace Bileto\CronnerTests\TimestampStorage;

require_once(__DIR__ . '/../../../bootstrap.php');

use DateTime;
use Nette;
use Nette\Utils\FileSystem;
use Bileto\Cronner\TimestampStorage\FileStorage;
use Tester\Assert;
use Tester\TestCase;

/**
 * @testCase
 */
class FileStorageTest extends TestCase
{

	/** @var FileStorage */
	private $storage;

	private static function getTempDirPath(): string
	{
		return TEMP_DIR . '/cronner';
	}

	public function testIsAbleToSetTaskName(): void
	{
		$this->storage->setTaskName('Test task 1');
		$this->storage->setTaskName(null);
		$this->storage->setTaskName();

		Assert::$counter++; // Hack for nette tester
	}

	/**
	 * @dataProvider dataProviderSetTaskName
	 * @throws \Bileto\Cronner\Exceptions\InvalidTaskNameException
	 */
	public function testThrowsExceptionOnInvalidTaskName(string $taskName = null): void
	{
		$this->storage->setTaskName($taskName);
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
	public function testLoadsAndSavesLastRunTimeWithoutErrors(DateTime $date): void
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
			[new Nette\Utils\DateTime('2013-01-30 17:30:00')],
			[new Nette\Utils\DateTime('2013-01-30 18:30:01')],
			[new Nette\Utils\DateTime('2013-01-30 18:31:01')],
		];
	}

	public function testSavesLastRunTimeByTaskName(): void
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

	protected function setUp(): void
	{
		parent::setUp();

		FileSystem::createDir(static::getTempDirPath());
		$this->storage = new FileStorage(static::getTempDirPath());
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		try {
			FileSystem::delete(static::getTempDirPath());
		} catch (Nette\IOException $e) {
		}
	}
}

(new FileStorageTest())->run();
