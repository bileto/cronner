<?php

declare(strict_types=1);

namespace Bileto\Cronner\tests\objects;

class SimpleTestObjectWithDependency
{

    public function __construct(FooService $service)
    {
    }

    /**
     * @cronner-task
     */
    public function run()
    {
    }
}

