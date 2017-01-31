<?php

namespace stekycz\Cronner\tests\objects;

use Exception;
use Nette\Object;

class TestExceptionObject extends Object
{

	/**
	 * @cronner-task
	 * @cronner-period 5 minutes
	 */
	public function test01()
	{
		throw new Exception('Test 01');
	}

	/**
	 * @cronner-task
	 * @cronner-period 5 minutes
	 */
	public function test02()
	{
	}

}
