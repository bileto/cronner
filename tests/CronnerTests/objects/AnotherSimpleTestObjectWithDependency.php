<?php

namespace stekycz\Cronner\tests\objects;

use Nette\Object;

class AnotherSimpleTestObjectWithDependency extends Object
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
