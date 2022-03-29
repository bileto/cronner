<?php

declare(strict_types=1);

namespace Bileto\CronnerTests\Tasks;

require_once(__DIR__ . '/../../../bootstrap.php');

use DateTime;
use Bileto\Cronner\Tasks\Parameters;
use Bileto\CronnerTests\Objects\TestObject;
use ReflectionClass;
use Tester\Assert;
use Tester\TestCase;

/**
 * @testCase
 */
class ParametersParsingTest extends TestCase
{
	private TestObject $object;

	protected function setUp(): void
	{
		parent::setUp();

		$this->object = new TestObject();
	}

	/**
	 * @dataProvider dataProviderParse
	 * @param array<mixed> $expected
	 * @param string $methodName
	 */
	public function testParsesTaskSettings(array $expected, string $methodName): void
	{
		$classType = new ReflectionClass($this->object);

		if (!$classType->hasMethod($methodName)) {
			Assert::fail('Tested class does not have method "' . $methodName . '".');

			return;
		}

		Assert::same($expected,
			Parameters::parseParameters(
				$classType->getMethod($methodName),
				new DateTime('NOW')
			)
		);
	}

	/**
	 * @return array<mixed>
	 */
	public function dataProviderParse(): array
	{
		return [
			[
				[
					Parameters::TASK => 'E-mail notifications',
					Parameters::PERIOD => '5 minutes',
					Parameters::DAYS => null,
					Parameters::DAYS_OF_MONTH => null,
					Parameters::TIME => null,
				],
				'test01',
			],
			[
				[
					Parameters::TASK => 'Bileto\CronnerTests\Objects\TestObject - test02',
					Parameters::PERIOD => '1 hour',
					Parameters::DAYS => ['Mon', 'Wed', 'Fri',],
					Parameters::DAYS_OF_MONTH => null,
					Parameters::TIME => [
						[
							'from' => '09:00',
							'to' => '10:00',
						],
						[
							'from' => '15:00',
							'to' => '16:00',
						],
					],
				],
				'test02',
			],
			[
				[
					Parameters::TASK => 'Test 3',
					Parameters::PERIOD => '17 minutes',
					Parameters::DAYS => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri',],
					Parameters::DAYS_OF_MONTH => null,
					Parameters::TIME => [
						[
							'from' => '09:00',
							'to' => '10:45',
						],
					],
				],
				'test03',
			],
			[
				[
					Parameters::TASK => 'Test 4',
					Parameters::PERIOD => '1 day',
					Parameters::DAYS => ['Sat', 'Sun',],
					Parameters::DAYS_OF_MONTH => null,
					Parameters::TIME => null,
				],
				'test04',
			],
		];
	}
}

(new ParametersParsingTest())->run();
