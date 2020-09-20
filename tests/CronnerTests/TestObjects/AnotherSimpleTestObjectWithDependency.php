<?php

declare(strict_types=1);

namespace CronnerTests\TestObjects;

class AnotherSimpleTestObjectWithDependency
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
