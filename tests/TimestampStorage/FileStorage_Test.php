<?php

namespace stekycz\Cronner\tests\TimestampStorage;

use PHPUnit_Framework_TestCase;
use Nette\Utils\Finder;
use stdClass;
use DateTime;
use stekycz\Cronner\TimestampStorage\FileStorage;
use Nette;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-21
 */
class FileStorage_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @var \stekycz\Cronner\TimestampStorage\FileStorage
	 */
	private $storage;

	protected function setUp() {
		parent::setUp();
		$this->storage = new FileStorage(static::getTempDirPath());
	}

	protected function tearDown() {
		parent::tearDown();
		/** @var \SplFileInfo $file */
		foreach (Finder::find('*')->from(static::getTempDirPath()) as $file) {
			unlink($file->getPathname());
		}
	}

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
        if (!file_exists(static::getTempDirPath())) {
            mkdir(static::getTempDirPath(), 777);
        }
	}

	private static function getTempDirPath() {
		return TEST_DIR . '/temp/cronner';
	}

	/**
	 * @test
	 */
	public function isAbleToSetTaskName() {
		$this->storage->setTaskName('Test task 1');
		$this->storage->setTaskName(null);
		$this->storage->setTaskName();
	}

	/**
	 * @test
	 * @dataProvider dataProviderSetTaskName
	 * @expectedException \stekycz\Cronner\InvalidTaskNameException
	 */
	public function throwsExceptionOnInvalidTaskName($taskName) {
		$this->storage->setTaskName($taskName);
	}

	public function dataProviderSetTaskName() {
		return array(
			array(''),
			array(0),
			array(1),
			array(0.0),
			array(1.0),
			array(false),
			array(true),
			array(new stdClass()),
			array(array()),
			array(array('Test task 1')),
		);
	}

	/**
	 * Tests that saving do not throws any exception.
	 *
	 * @test
	 * @dataProvider dataProviderSaveRunTime
	 * @param \DateTime $date
	 */
	public function loadsAndSavesLastRunTimeWithoutErrors(DateTime $date) {
		$this->storage->setTaskName('Test task 1');

		$lastRunTime = $this->storage->loadLastRunTime();
		$this->assertNull($lastRunTime);
		$this->storage->saveRunTime($date);
		$lastRunTime = $this->storage->loadLastRunTime();

		$this->storage->setTaskName();

		$this->assertNotNull($lastRunTime);
		$this->assertEquals($date, $lastRunTime);
	}

	public function dataProviderSaveRunTime() {
		return array(
			array(new Nette\DateTime('2013-01-30 17:30:00')),
			array(new Nette\DateTime('2013-01-30 18:30:01')),
			array(new Nette\DateTime('2013-01-30 18:31:01')),
		);
	}

	/**
	 * @test
	 */
	public function savesLastRunTimeByTaskName() {
		$date = new Nette\DateTime('2013-01-30 17:30:00');

		$this->storage->setTaskName('Test task 1');
		$lastRunTime = $this->storage->loadLastRunTime();
		$this->assertNull($lastRunTime);
		$this->storage->saveRunTime($date);
		$lastRunTime = $this->storage->loadLastRunTime();

		$this->storage->setTaskName();

		$this->assertNotNull($lastRunTime);
		$this->assertEquals($date, $lastRunTime);

		$this->storage->setTaskName('Test task 2');
		$lastRunTime = $this->storage->loadLastRunTime();
		$this->assertNull($lastRunTime);
		$this->storage->saveRunTime($date);
		$lastRunTime = $this->storage->loadLastRunTime();

		$this->storage->setTaskName();

		$this->assertNotNull($lastRunTime);
		$this->assertEquals($date, $lastRunTime);
	}

}
