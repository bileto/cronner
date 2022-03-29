<?php

declare(strict_types=1);

namespace Bileto\CronnerTests\Objects;

class AnotherSimpleTestObjectWithDependency
{
	private $service;

	public function __construct(FooService $service)
	{
		$this->service = $service;
	}

	/**
	 * @cronner-task
	 */
	public function run(): void
	{
		$this->service->run();
	}
}
