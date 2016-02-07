<?php

namespace stekycz\Cronner\tests\objects;

use Nette\Object;


/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class AnotherSimpleTestObject extends Object
{

	/**
	 * @cronner-task Test
	 */
	public function test01()
	{
	}

}
