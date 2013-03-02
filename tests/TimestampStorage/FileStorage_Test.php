<?php

namespace stekycz\Cronner\tests\TimestampStorage;

use PHPUnit_Framework_TestCase;
use stdClass;
use DateTime;
use stekycz\Cronner\TimestampStorage\FileStorage;
use Nette;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-21
 */
class FileStorage_Test extends PHPUnit_Framework_TestCase {

	const TEST_TASK_NAME = 'Testing task';

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
		unlink(static::getTempDirPath() . '/' . sha1(static::TEST_TASK_NAME));
	}

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		mkdir(static::getTempDirPath());
		chmod(static::getTempDirPath(), '+rwx');
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		rmdir(static::getTempDirPath());
	}

	private static function getTempDirPath() {
		return __DIR__ . '/temp';
	}

	/**
	 * @test
	 */
	public function isAbleToSetTaskName() {
		$this->storage->setTaskName('test 1');
		$this->storage->setTaskName(null);
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
			array(array('test 1')),
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
		$this->storage->setTaskName(static::TEST_TASK_NAME);

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

		$this->storage->setTaskName('test 1');
		$lastRunTime = $this->storage->loadLastRunTime();
		$this->assertNull($lastRunTime);
		$this->storage->saveRunTime($date);
		$lastRunTime = $this->storage->loadLastRunTime();

		$this->storage->setTaskName();

		$this->assertNotNull($lastRunTime);
		$this->assertEquals($date, $lastRunTime);

		$this->storage->setTaskName('test 2');
		$lastRunTime = $this->storage->loadLastRunTime();
		$this->assertNull($lastRunTime);
		$this->storage->saveRunTime($date);
		$lastRunTime = $this->storage->loadLastRunTime();

		$this->storage->setTaskName();

		$this->assertNotNull($lastRunTime);
		$this->assertEquals($date, $lastRunTime);
	}

}
