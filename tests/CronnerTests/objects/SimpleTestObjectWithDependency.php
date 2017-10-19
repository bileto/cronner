<?php

namespace stekycz\Cronner\tests\objects;

use Nette\Object;

class SimpleTestObjectWithDependency extends Object
{

	public function __construct(FooService $service)
	{
	}

	/**
	 * @cronner-task
	 */
	public function run()
	{
	}
}

