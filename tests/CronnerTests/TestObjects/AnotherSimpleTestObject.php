<?php

declare(strict_types=1);

namespace CronnerTests\TestObjects;

use Nette\SmartObject;

class AnotherSimpleTestObject
{
    use SmartObject;

    /**
     * @cronner-task Test
     */
    public function test01()
    {
    }

}
