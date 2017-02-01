<?php

declare(strict_types=1);

namespace stekycz\Cronner\tests\objects;

use Nette\Object;

class NextSimpleTestObject extends Object
{

	/**
	 * @cronner-task Test
	 */
	public function test01()
	{
	}

}
