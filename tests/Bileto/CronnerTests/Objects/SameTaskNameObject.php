<?php

declare(strict_types=1);

namespace Bileto\CronnerTests\Objects;

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
