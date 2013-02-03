<?php

namespace stekycz\Cronner;

use Nette\Object;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */
abstract class Tasks extends Object {

	const DEFAULT_NAME = 'e7d746120e932d5e07c1c09c4b799f1924c2cc1a';

	/**
	 * Returns name of task case. It is used internally so it can
	 * be some hash.
	 *
	 * @return string
	 */
	public function getName() {
		return static::DEFAULT_NAME;
	}

}
