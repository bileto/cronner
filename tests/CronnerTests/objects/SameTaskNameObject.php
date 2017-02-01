<?php

declare(strict_types=1);

namespace stekycz\Cronner\tests\objects;

use Nette\Object;

class SameTaskNameObject extends Object
{

	/**
	 * @cronner-task Test
	 */
	public function test01()
	{
	}

	/**
	 * @cronner-task Test
	 */
	public function test02()
	{
	}

}
