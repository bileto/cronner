<?php

namespace stekycz\Cronner\tests\objects;

use Nette\Object;
use Exception;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-03-03
 */
class TestExceptionObject extends Object {

	/**
	 * @cronner-task
	 * @cronner-period 5 minutes
	 */
	public function test01() {
		throw new Exception('Test 01');
	}

	/**
	 * @cronner-task
	 * @cronner-period 5 minutes
	 */
	public function test02() {
	}

}
