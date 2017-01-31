<?php

/**
 * @testCase
 */

namespace stekycz\Cronner\tests\TimestampStorage;

use DateTime;
use Nette;
use Nette\Utils\FileSystem;
use stdClass;
use stekycz\Cronner\TimestampStorage\FileStorage;
use Tester\Assert;

require_once(__DIR__ . "/../bootstrap.php");

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class FileStorageTest extends \TestCase
{

	/**
	 * @var \stekycz\Cronner\TimestampStorage\FileStorage
	 */
	private $storage;



	protected function setUp()
	{
		parent::setUp();
		FileSystem::createDir(static::getTempDirPath());
		$this->storage = new FileStorage(static::getTempDirPath());
	}



	protected function tearDown()
	{
		parent::tearDown();
		FileSystem::delete(static::getTempDirPath());
	}



	private static function getTempDirPath()
	{
		return TEMP_DIR . '/cronner';
	}



	public function testIsAbleToSetTaskName()
	{
		$this->storage->setTaskName('Test task 1');
		$this->storage->setTaskName(NULL);
		$this->storage->setTaskName();
		Assert::$counter++; // Hack for nette tester
	}



	/**
	 * @dataProvider dataProviderSetTaskName
	 * @throws \stekycz\Cronner\Exceptions\InvalidTaskNameException
	 */
	public function testThrowsExceptionOnInvalidTaskName($taskName)
	{
		$this->storage->setTaskName($taskName);
	}



	public function dataProviderSetTaskName()
	{
		return array(
			array(''),
			array(0),
			array(1),
			array(0.0),
			array(1.0),
			array(FALSE),
			array(TRUE),
			array(new stdClass()),
			array(array()),
			array(array('Test task 1')),
		);
	}



	/**
	 * Tests that saving do not throws any exception.
	 *
	 * @dataProvider dataProviderSaveRunTime
	 * @param \DateTime $date
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



	public function dataProviderSaveRunTime()
	{
		return array(
			array(new Nette\Utils\DateTime('2013-01-30 17:30:00')),
			array(new Nette\Utils\DateTime('2013-01-30 18:30:01')),
			array(new Nette\Utils\DateTime('2013-01-30 18:31:01')),
		);
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

run(new FileStorageTest());
