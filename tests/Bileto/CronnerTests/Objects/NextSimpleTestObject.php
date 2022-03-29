<?php

declare(strict_types=1);

namespace Bileto\CronnerTests\Objects;

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
