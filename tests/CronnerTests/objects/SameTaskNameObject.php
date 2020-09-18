<?php

declare(strict_types=1);

namespace CronnerTests\objects;

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
