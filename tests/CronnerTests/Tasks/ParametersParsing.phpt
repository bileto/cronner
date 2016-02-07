<?php

/**
 * @testCase
 */

namespace stekycz\Cronner\tests\Tasks;

use stekycz\Cronner\Tasks\Parameters;
use stekycz\Cronner\tests\objects\TestObject;
use Tester\Assert;



require_once(__DIR__ . "/../bootstrap.php");

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class ParametersParsingTest extends \TestCase
{

	/**
	 * @var object
	 */
	private $object;



	protected function setUp()
	{
		parent::setUp();
		$this->object = new TestObject();
	}



	/**
	 * @dataProvider dataProviderParse
	 * @param array $expected
	 * @param string $methodName
	 */
	public function testParsesTaskSettings(array $expected, $methodName)
	{
		if (!$this->object->getReflection()->hasMethod($methodName)) {
			Assert::fail('Tested class does not have method "' . $methodName . '".');

			return;
		}

		Assert::same($expected, Parameters::parseParameters($this->object->getReflection()->getMethod($methodName)));
	}



	public function dataProviderParse()
	{
		return array(
			array(array(
				Parameters::TASK => 'E-mail notifications',
				Parameters::PERIOD => '5 minutes',
				Parameters::DAYS => NULL,
				Parameters::TIME => NULL,
			), 'test01'
			),
			array(array(
				Parameters::TASK => 'stekycz\Cronner\tests\objects\TestObject - test02',
				Parameters::PERIOD => '1 hour',
				Parameters::DAYS => array('Mon', 'Wed', 'Fri',),
				Parameters::TIME => array(
					array(
						'from' => '09:00',
						'to' => '10:00',
					),
					array(
						'from' => '15:00',
						'to' => '16:00',
					),
				),
			), 'test02'
			),
			array(array(
				Parameters::TASK => 'Test 3',
				Parameters::PERIOD => '17 minutes',
				Parameters::DAYS => array('Mon', 'Tue', 'Wed', 'Thu', 'Fri',),
				Parameters::TIME => array(
					array(
						'from' => '09:00',
						'to' => '10:45',
					),
				),
			), 'test03'
			),
			array(array(
				Parameters::TASK => 'Test 4',
				Parameters::PERIOD => '1 day',
				Parameters::DAYS => array('Sat', 'Sun',),
				Parameters::TIME => NULL
			), 'test04'
			),
		);
	}

}

run(new ParametersParsingTest());
