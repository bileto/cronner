<?php

namespace stekycz\Cronner;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class InvalidArgumentException extends \InvalidArgumentException
{

}



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class InvalidParameterException extends InvalidArgumentException
{

}



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class InvalidTaskNameException extends InvalidArgumentException
{

}



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class EmptyTaskNameException extends InvalidArgumentException
{

}



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class DuplicateTaskNameException extends InvalidArgumentException
{

}



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class RuntimeException extends \RuntimeException
{

}
