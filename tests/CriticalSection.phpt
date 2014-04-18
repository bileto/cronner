<?php

/**
 * @testCase
 */

namespace stekycz\Cronner\tests;

use Nette\Utils\FileSystem;
use stekycz\Cronner\CriticalSection;
use Tester\Assert;



require_once(__DIR__ . "/bootstrap.php");

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class CriticalSectionTest extends \TestCase
{

	const TEST_LABEL = "test";

	/**
	 * @var \stekycz\Cronner\CriticalSection
	 */
	private $criticalSection;



	protected function setUp()
	{
		parent::setUp();
		$filesDir = TEMP_DIR . "/cronner/critical-section";
		FileSystem::createDir($filesDir);
		$this->criticalSection = new CriticalSection($filesDir);
	}



	protected function tearDown()
	{
		parent::tearDown();
		if ($this->criticalSection->isEntered(self::TEST_LABEL)) {
			$this->criticalSection->leave(self::TEST_LABEL);
		}
	}



	public function testCanBeEnteredAndLeaved()
	{
		Assert::false($this->criticalSection->isEntered(self::TEST_LABEL));
		Assert::true($this-> criticalSection->enter(self::TEST_LABEL));
		Assert::true($this->criticalSection->isEntered(self::TEST_LABEL));
		Assert::true($this->criticalSection->leave(self::TEST_LABEL));
		Assert::false($this->criticalSection->isEntered(self::TEST_LABEL));
	}



	public function testCannotBeEnteredTwice()
	{
		Assert::true($this->criticalSection->enter(self::TEST_LABEL));
		Assert::true($this->criticalSection->isEntered(self::TEST_LABEL));
		Assert::false($this->criticalSection->enter(self::TEST_LABEL));
	}



	public function testCannotBeLeavedWithoutEnter()
	{
		Assert::false($this->criticalSection->isEntered(self::TEST_LABEL));
		Assert::false($this->criticalSection->leave(self::TEST_LABEL));
	}



	public function testCannotBeLeavedTwice()
	{
		Assert::true($this->criticalSection->enter(self::TEST_LABEL));
		Assert::true($this->criticalSection->isEntered(self::TEST_LABEL));
		Assert::true($this->criticalSection->leave(self::TEST_LABEL));
		Assert::false($this->criticalSection->leave(self::TEST_LABEL));
	}

}

run(new CriticalSectionTest());
