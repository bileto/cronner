<?php

declare(strict_types=1);

namespace CronnerTests\TestObjects;

use Nette\SmartObject;

class SameTaskNameObject
{
    use SmartObject;

    /**
     * @cronner-task Test
     */
    public function test01()
    {
    }

    /**
     * @cronner-task Test
     */
    public function test02()
    {
    }

}
