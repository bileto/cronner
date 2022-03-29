<?php

declare(strict_types=1);

namespace Bileto\CronnerTests\Objects;

use Nette\SmartObject;

class TestObject
{
	use SmartObject;

	/**
	 * @cronner-task
	 * @cronner-period 1 day
	 */
	public function __construct()
	{
	}

	/**
	 * @cronner-task E-mail notifications
	 * @cronner-period 5 minutes
	 */
	public function test01(): void
	{
	}

	/**
	 * @cronner-task
	 * @cronner-period 1 hour
	 * @cronner-days Mon, Wed, Fri
	 * @cronner-time 09:00 - 10:00, 15:00 - 16:00
	 */
	public function test02(): void
	{
	}

	/**
	 * @cronner-task Test 3
	 * @cronner-period 17 minutes
	 * @cronner-days working days
	 * @cronner-time 09:00 - 10:45
	 */
	public function test03(): void
	{
	}

	/**
	 * @cronner-task Test 4
	 * @cronner-period 1 day
	 * @cronner-days weekend
	 */
	public function test04(): void
	{
	}
}
