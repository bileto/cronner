<?php

namespace stekycz\Cronner;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */
class InvalidArgumentException extends \InvalidArgumentException {
}

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-04
 */
class InvalidParameter extends InvalidArgumentException {
}

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-03-02
 */
class InvalidTaskNameException extends InvalidArgumentException {
}

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-03-02
 */
class EmptyTaskNameException extends InvalidArgumentException {
}

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-21
 */
class RuntimeException extends \RuntimeException {
}

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-21
 */
class IOException extends RuntimeException
{
}

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-21
 */
class FileNotFoundException extends IOException
{
}

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-21
 */
class FileCannotBeOpenedException extends IOException
{
}

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-21
 */
class FileCannotBeClosedException extends IOException
{
}

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-21
 */
class DirectoryNotFoundException extends IOException {
}
