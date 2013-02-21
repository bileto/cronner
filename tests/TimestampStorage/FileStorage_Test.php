<?php

namespace stekycz\Cronner\tests\TimestampStorage;

use PHPUnit_Framework_TestCase;
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
		$this->storage = new FileStorage(static::getTestFilePath());
	}

	protected function tearDown() {
		parent::tearDown();
		unlink(static::getTestFilePath());
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

	private static function getTestFilePath() {
		return static::getTempDirPath() . '/cronner-test.task';
	}

	/**
	 * Tests that saving do not throws any exception.
	 *
	 * @test
	 * @dataProvider dataProviderSaveRunTime
	 * @param \DateTime $date
	 */
	public function loadsAndSavesLastRunTimeWithoutErrors(DateTime $date) {
		$lastRunTime = $this->storage->loadLastRunTime();
		$this->assertNull($lastRunTime);
		$this->storage->saveRunTime($date);
		$lastRunTime = $this->storage->loadLastRunTime();
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

}
