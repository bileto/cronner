<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace stekycz\Cronner\tests\Tasks;

use DateTime;
use DateTimeInterface;
use Nette;
use stekycz\Cronner\Tasks\Parameters;
use Tester\Assert;

require_once(__DIR__ . "/../bootstrap.php");

class ParametersTest extends \TestCase
{

	/**
	 * @dataProvider dataProviderGetName
	 * @param string $expected
	 * @param array $parameters
	 */
	public function testReturnsTaskName(string $expected, array $parameters)
	{
		$params = new Parameters($parameters);
		Assert::equal($expected, $params->getName());
	}

	public function dataProviderGetName() : array
	{
		return [
			['Name of task', [Parameters::TASK => 'Name of task',]],
			['0', [Parameters::TASK => '0',]],
			['', [Parameters::TASK => '   ',]],
			['', [Parameters::TASK => '',]],
			['', [Parameters::TASK => TRUE,]],
			['', [Parameters::TASK => FALSE,]],
			['', [Parameters::TASK => NULL,]],
			['', [Parameters::TASK => 0,]],
			['', []],
		];
	}

	/**
	 * @dataProvider dataProviderIsTask
	 * @param bool $expected
	 * @param array $parameters
	 */
	public function testDetectsTask(bool $expected, array $parameters)
	{
		$params = new Parameters($parameters);
		Assert::equal($expected, $params->isTask());
	}

	public function dataProviderIsTask() : array
	{
		return [
			[TRUE, [Parameters::TASK => 'Name of task',]],
			[TRUE, [Parameters::TASK => '0',]],
			[FALSE, [Parameters::TASK => '   ',]],
			[FALSE, [Parameters::TASK => '',]],
			[FALSE, [Parameters::TASK => TRUE,]],
			[FALSE, [Parameters::TASK => FALSE,]],
			[FALSE, [Parameters::TASK => NULL,]],
			[FALSE, [Parameters::TASK => 0,]],
			[FALSE, []],
		];
	}

	/**
	 * @dataProvider dataProviderIsNextPeriod
	 * @param bool $expected
	 * @param DateTimeInterface $now
	 * @param DateTimeInterface|null $lastRunTime
	 * @param array $parameters
	 */
	public function testDetectsIfNowIsInNextPeriod(bool $expected, DateTimeInterface $now, DateTimeInterface $lastRunTime = NULL, array $parameters)
	{
		$params = new Parameters($parameters);
		Assert::same($expected, $params->isNextPeriod($now, $lastRunTime));
	}

	public function dataProviderIsNextPeriod() : array
	{
		return [
			[
				TRUE,
				new \DateTimeImmutable('2013-02-03 17:00:00'),
				new \DateTimeImmutable('2013-02-03 16:54:59'),
				[Parameters::PERIOD => '5 minutes',],
			],
			[
				TRUE,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:54:59'),
				[Parameters::PERIOD => '5 minutes',],
			],
			[
				TRUE,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:55:00'),
				[Parameters::PERIOD => '5 minutes',],
			],
			[
				TRUE,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:55:01'),
				[Parameters::PERIOD => '5 minutes',],
			],
			[
				TRUE,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:55:05'),
				[Parameters::PERIOD => '5 minutes',],
			],
			[
				FALSE,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:55:06'),
				[Parameters::PERIOD => '5 minutes',],
			],
			[
				FALSE,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:55:01'),
				[Parameters::PERIOD => '1 hour',],
			],
			[
				TRUE,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:00:00'),
				[Parameters::PERIOD => '1 hour',],
			],
			[
				TRUE,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:00:00'),
				[],
			],
			[
				TRUE,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				NULL,
				[Parameters::PERIOD => '1 hour',],
			],
			[
				TRUE,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				NULL,
				[],
			],
		];
	}

	/**
	 * @dataProvider dataProviderIsInDay
	 * @param bool $expected
	 * @param array $parameters
	 * @param DateTime $now
	 */
	public function testDetectsAllowedDaysOfWeek(bool $expected, array $parameters, DateTime $now)
	{
		$params = new Parameters($parameters);
		Assert::equal($expected, $params->isInDay($now));
	}

	public function dataProviderIsInDay() : array
	{
		return [
			// One day
			[TRUE, [Parameters::DAYS => ['Mon',],], new Nette\Utils\DateTime('2013-02-11 12:34:56')],
			[FALSE, [Parameters::DAYS => ['Mon',],], new Nette\Utils\DateTime('2013-02-12 12:34:56')],
			[TRUE, [Parameters::DAYS => ['Tue',],], new Nette\Utils\DateTime('2013-02-12 12:34:56')],
			[FALSE, [Parameters::DAYS => ['Tue',],], new Nette\Utils\DateTime('2013-02-13 12:34:56')],
			[TRUE, [Parameters::DAYS => ['Wed',],], new Nette\Utils\DateTime('2013-02-13 12:34:56')],
			[FALSE, [Parameters::DAYS => ['Wed',],], new Nette\Utils\DateTime('2013-02-14 12:34:56')],
			[TRUE, [Parameters::DAYS => ['Thu',],], new Nette\Utils\DateTime('2013-02-14 12:34:56')],
			[FALSE, [Parameters::DAYS => ['Thu',],], new Nette\Utils\DateTime('2013-02-15 12:34:56')],
			[TRUE, [Parameters::DAYS => ['Fri',],], new Nette\Utils\DateTime('2013-02-15 12:34:56')],
			[FALSE, [Parameters::DAYS => ['Fri',],], new Nette\Utils\DateTime('2013-02-16 12:34:56')],
			[TRUE, [Parameters::DAYS => ['Sat',],], new Nette\Utils\DateTime('2013-02-16 12:34:56')],
			[FALSE, [Parameters::DAYS => ['Sat',],], new Nette\Utils\DateTime('2013-02-17 12:34:56')],
			[TRUE, [Parameters::DAYS => ['Sun',],], new Nette\Utils\DateTime('2013-02-17 12:34:56')],
			[FALSE, [Parameters::DAYS => ['Sun',],], new Nette\Utils\DateTime('2013-02-18 12:34:56')],
			// Empty days
			[FALSE, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-11 12:34:56')],
			[FALSE, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-12 12:34:56')],
			[FALSE, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-13 12:34:56')],
			[FALSE, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-14 12:34:56')],
			[FALSE, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-15 12:34:56')],
			[FALSE, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-16 12:34:56')],
			[FALSE, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-17 12:34:56')],
			// Without days
			[TRUE, [Parameters::DAYS => NULL,], new Nette\Utils\DateTime('2013-02-11 12:34:56')],
			[TRUE, [Parameters::DAYS => NULL,], new Nette\Utils\DateTime('2013-02-12 12:34:56')],
			[TRUE, [Parameters::DAYS => NULL,], new Nette\Utils\DateTime('2013-02-13 12:34:56')],
			[TRUE, [Parameters::DAYS => NULL,], new Nette\Utils\DateTime('2013-02-14 12:34:56')],
			[TRUE, [Parameters::DAYS => NULL,], new Nette\Utils\DateTime('2013-02-15 12:34:56')],
			[TRUE, [Parameters::DAYS => NULL,], new Nette\Utils\DateTime('2013-02-16 12:34:56')],
			[TRUE, [Parameters::DAYS => NULL,], new Nette\Utils\DateTime('2013-02-17 12:34:56')],
		];
	}

	/**
	 * @dataProvider dataProviderIsInTime
	 * @param bool $expected
	 * @param array $parameters
	 * @param string $now
	 */
	public function testDetectsAllowedTimeRange(bool $expected, array $parameters, string $now)
	{
		$now = new Nette\Utils\DateTime($now);
		$params = new Parameters($parameters);
		Assert::equal($expected, $params->isInTime($now));
	}

	public function dataProviderIsInTime() : array
	{
		return [
			// One minute
			[
				TRUE,
				[
					Parameters::TIME => [
						[
							'from' => '11:00',
							'to' => NULL,
						],
					],
				],
				'2013-02-11 11:00:00',
			],
			[
				TRUE,
				[
					Parameters::TIME => [
						[
							'from' => '11:00',
							'to' => NULL,
						],
					],
				],
				'2013-02-11 11:00:59',
			],
			[
				FALSE,
				[
					Parameters::TIME => [
						[
							'from' => '11:00',
							'to' => NULL,
						],
					],
				],
				'2013-02-11 10:59:59',
			],
			[
				FALSE,
				[
					Parameters::TIME => [
						[
							'from' => '11:00',
							'to' => NULL,
						],
					],
				],
				'2013-02-11 11:01:00',
			],
			// Range
			[
				TRUE,
				[
					Parameters::TIME => [
						[
							'from' => '11:00',
							'to' => '12:00',
						],
					],
				],
				'2013-02-11 11:00:00',
			],
			[
				TRUE,
				[
					Parameters::TIME => [
						[
							'from' => '11:00',
							'to' => '12:00',
						],
					],
				],
				'2013-02-11 11:30:00',
			],
			[
				TRUE,
				[
					Parameters::TIME => [
						['from' => '11:00', 'to' => '12:00',],
					],
				],
				'2013-02-11 12:00:59',
			],
			[
				FALSE,
				[
					Parameters::TIME => [
						['from' => '11:00', 'to' => '12:00',],
					],
				],
				'2013-02-11 10:59:59',
			],
			[
				FALSE,
				[
					Parameters::TIME => [
						['from' => '11:00', 'to' => '12:00',],
					],
				],
				'2013-02-11 12:01:00',
			],
			// Empty
			[
				TRUE,
				[Parameters::TIME => [],],
				'2013-02-11 12:00:00',
			],
			[
				TRUE,
				[Parameters::TIME => NULL,],
				'2013-02-11 12:00:00',
			],
		];
	}

}

run(new ParametersTest());
