<?php

declare(strict_types=1);

namespace Bileto\CronnerTests\Objects;

use Nette\SmartObject;

class AnotherSimpleTestObject
{
	use SmartObject;

	/**
	 * @cronner-task Test
	 */
	public function test01(): void
	{
	}
}
