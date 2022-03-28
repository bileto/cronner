<?php

declare(strict_types=1);

namespace Bileto\Cronner\tests\objects;


class SameTaskNameObject
{
	use \Nette\SmartObject;


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
