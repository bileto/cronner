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
class RuntimeException extends \RuntimeException
{

}



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class IOException extends RuntimeException
{

}



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class FileNotFoundException extends IOException
{

}



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class FileCannotBeOpenedException extends IOException
{

}



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class FileCannotBeClosedException extends IOException
{

}



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class DirectoryNotFoundException extends IOException
{

}
