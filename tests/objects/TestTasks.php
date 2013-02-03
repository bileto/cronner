<?php

namespace stekycz\Cronner\tests\objects;

use stekycz\Cronner\Tasks;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */
class TestTasks extends Tasks {

	/**
	 * @cronner-task E-mail notifications
	 * @cronner-period 5 minutes
	 */
	public function sendEmailNotifications() {
	}

}
