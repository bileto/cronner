<?php

declare(strict_types=1);

namespace CronnerTests\objects;

use Nette\SmartObject;

class NextSimpleTestObject
{
    use SmartObject;

    /**
     * @cronner-task Test
     */
    public function test01()
    {
    }

}
