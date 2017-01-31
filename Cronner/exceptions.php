<?php

namespace stekycz\Cronner;

class InvalidArgumentException extends \InvalidArgumentException
{

}

class InvalidParameterException extends InvalidArgumentException
{

}

class InvalidTaskNameException extends InvalidArgumentException
{

}

class EmptyTaskNameException extends InvalidArgumentException
{

}

class DuplicateTaskNameException extends InvalidArgumentException
{

}

class RuntimeException extends \RuntimeException
{

}
