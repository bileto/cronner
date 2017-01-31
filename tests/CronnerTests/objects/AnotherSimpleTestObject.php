<?php

namespace stekycz\Cronner\tests\objects;

use Nette\Object;

class AnotherSimpleTestObject extends Object
{

	/**
	 * @cronner-task Test
	 */
	public function test01()
	{
	}

}
