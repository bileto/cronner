<?php

namespace stekycz\Cronner\tests;

use stekycz\Cronner\CriticalSection;
use Tester\Assert;



require_once(__DIR__ . "/bootstrap.php");

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class CriticalSectionTest extends \TestCase
{

	protected function tearDown()
	{
		parent::tearDown();
		if (CriticalSection::isEntered()) {
			CriticalSection::leave();
		}
	}



	public function testCanBeEnteredAndLeaved()
	{
		Assert::true(CriticalSection::enter());
		Assert::true(CriticalSection::isEntered());
		Assert::true(CriticalSection::leave());
	}



	public function testCannotBeEnteredTwice()
	{
		Assert::true(CriticalSection::enter());
		Assert::true(CriticalSection::isEntered());
		Assert::false(CriticalSection::enter());
	}



	public function testCannotBeLeavedWithoutEnter()
	{
		Assert::false(CriticalSection::isEntered());
		Assert::false(CriticalSection::leave());
	}



	public function testCannotBeLeavedTwice()
	{
		Assert::true(CriticalSection::enter());
		Assert::true(CriticalSection::isEntered());
		Assert::true(CriticalSection::leave());
		Assert::false(CriticalSection::leave());
	}

}

run(new CriticalSectionTest());
