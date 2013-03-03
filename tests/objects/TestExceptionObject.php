<?php

namespace stekycz\Cronner\tests\objects;

use Nette\Object;
use Exception;
use stekycz\Cronner\ITasksContainer;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-03-03
 */
class TestExceptionObject extends Object implements ITasksContainer {

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
