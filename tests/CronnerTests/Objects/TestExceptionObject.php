<?php

declare(strict_types=1);

namespace CronnerTests\Objects;

use Exception;
use Nette\SmartObject;

class TestExceptionObject
{
    use SmartObject;

    /**
     * @cronner-task
     * @cronner-period 5 minutes
     * @throws Exception
     */
    public function test01()
    {
        throw new Exception('Test 01');
    }

    /**
     * @cronner-task
     * @cronner-period 5 minutes
     */
    public function test02()
    {
    }

}
