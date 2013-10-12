<?php

namespace stekycz\Cronner\tests\TimestampStorage;

use DateTime;
use Nette\Utils\Finder;
use Nette;
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
		if (!file_exists(static::getTempDirPath())) {
			mkdir(static::getTempDirPath(), 0777, TRUE);
		}
		$this->storage = new FileStorage(static::getTempDirPath());
	}



	protected function tearDown()
	{
		parent::tearDown();
		/** @var \SplFileInfo $file */
		foreach (Finder::find('*')->from(static::getTempDirPath()) as $file) {
			unlink($file->getPathname());
		}
	}



	private static function getTempDirPath()
	{
		return TEST_DIR . '/temp/cronner';
	}



	public function testIsAbleToSetTaskName()
	{
		$this->storage->setTaskName('Test task 1');
		$this->storage->setTaskName(NULL);
		$this->storage->setTaskName();
	}



	/**
	 * @dataProvider dataProviderSetTaskName
	 * @throws \stekycz\Cronner\InvalidTaskNameException
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

		Assert::true($lastRunTime !== NULL);
		Assert::equal($date, $lastRunTime);
	}



	public function dataProviderSaveRunTime()
	{
		return array(
			array(new Nette\DateTime('2013-01-30 17:30:00')),
			array(new Nette\DateTime('2013-01-30 18:30:01')),
			array(new Nette\DateTime('2013-01-30 18:31:01')),
		);
	}



	public function testSavesLastRunTimeByTaskName()
	{
		$date = new Nette\DateTime('2013-01-30 17:30:00');

		$this->storage->setTaskName('Test task 1');
		$lastRunTime = $this->storage->loadLastRunTime();
		Assert::null($lastRunTime);
		$this->storage->saveRunTime($date);
		$lastRunTime = $this->storage->loadLastRunTime();

		$this->storage->setTaskName();

		Assert::true($lastRunTime !== NULL);
		Assert::equal($date, $lastRunTime);

		$this->storage->setTaskName('Test task 2');
		$lastRunTime = $this->storage->loadLastRunTime();
		Assert::null($lastRunTime);
		$this->storage->saveRunTime($date);
		$lastRunTime = $this->storage->loadLastRunTime();

		$this->storage->setTaskName();

		Assert::true($lastRunTime !== NULL);
		Assert::equal($date, $lastRunTime);
	}

}

run(new FileStorageTest());
