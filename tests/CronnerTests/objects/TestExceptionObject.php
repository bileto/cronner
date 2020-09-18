<?php

declare(strict_types=1);

namespace Bileto\Cronner\tests\objects;

use Exception;
use Nette\SmartObject;

class TestExceptionObject
{
    use SmartObject;

    /**
     * @cronner-task
     * @cronner-period 5 minutes
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
