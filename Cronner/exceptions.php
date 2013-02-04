<?php

namespace stekycz\Cronner;

use \Nette;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */
class InvalidArgumentException extends Nette\InvalidArgumentException {
}

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-04
 */
class InvalidParameter extends InvalidArgumentException {
}
