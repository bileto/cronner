<?php

/**
 * @testCase
 */

namespace stekycz\Cronner\tests;

use Nette\Utils\FileSystem;
use stekycz\Cronner\CriticalSection;
use Tester\Assert;

require_once(__DIR__ . "/bootstrap.php");

class CriticalSectionTest extends \TestCase
{

	const TEST_LABEL = "test";

	/**
	 * @var CriticalSection
	 */
	private $criticalSection;

	/**
	 * @var string
	 */
	private $filesDir;

	protected function setUp()
	{
		parent::setUp();
		$this->filesDir = TEMP_DIR . "/cronner/critical-section";
		FileSystem::createDir($this->filesDir);
		$this->criticalSection = new CriticalSection($this->filesDir);
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
		Assert::true($this->criticalSection->enter(self::TEST_LABEL));
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

	public function testMultipleCriticalSectionHandlers()
	{
		$criticalSection = $this->criticalSection;
		$criticalSection2 = new CriticalSection($this->filesDir);

		Assert::false($criticalSection->isEntered(self::TEST_LABEL));
		Assert::false($criticalSection2->isEntered(self::TEST_LABEL));
		Assert::true($criticalSection->enter(self::TEST_LABEL));
		Assert::false($criticalSection2->enter(self::TEST_LABEL));
		Assert::true($criticalSection->isEntered(self::TEST_LABEL));
		Assert::false($criticalSection2->isEntered(self::TEST_LABEL));
		Assert::true($criticalSection->leave(self::TEST_LABEL));
		Assert::false($criticalSection2->leave(self::TEST_LABEL));
		Assert::false($criticalSection->isEntered(self::TEST_LABEL));
		Assert::false($criticalSection2->isEntered(self::TEST_LABEL));
	}

}

run(new CriticalSectionTest());
