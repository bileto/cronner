<?php

namespace stekycz\Cronner\tests\objects;

use stekycz\Cronner\Tasks;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */
class TestObject extends Tasks {

	/**
	 * @cronner-task E-mail notifications
	 * @cronner-period 5 minutes
	 */
	public function test01() {
	}

	/**
	 * @cronner-task
	 * @cronner-period 1 hour
	 * @cronner-days [Mo, We, Fr]
	 * @cronner-time [09:00 - 10:00, 15:00 - 16:00]
	 */
	public function test02() {
	}

}
