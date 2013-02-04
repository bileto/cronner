<?php

namespace stekycz\Cronner\tests;

require_once(TEST_DIR . '/objects/TestObject.php');

use PHPUnit_Framework_TestCase;
use stekycz\Cronner\tests\objects\TestObject;
use Nette\Reflection\ClassType;
use stekycz\Cronner\Tasks\Parameters;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-04
 */
class ParametersParsing_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @var \stekycz\Cronner\Tasks
	 */
	private $object;

	protected function setUp() {
		parent::setUp();
		$this->object = new TestObject();
	}

	/**
	 * @dataProvider dataProviderParse
	 * @param array $expected
	 * @param string $methodName
	 */
	public function testParse(array $expected, $methodName) {
		if (!$this->object->getReflection()->hasMethod($methodName)) {
			$this->fail('Tested class does not have method "' . $methodName . '".');
			return;
		}

		$this->assertSame($expected, Parameters::parseParameters($this->object->getReflection()->getMethod($methodName)));
	}

	public function dataProviderParse() {
		return array(
			array(array(
					Parameters::TASK => 'E-mail notifications',
					Parameters::PERIOD => '5 minutes',
					Parameters::DAYS => null,
					Parameters::TIME => null,
				), 'test01'
			),
			array(array(
					Parameters::TASK => 'stekycz\Cronner\tests\objects\TestObject - test02',
					Parameters::PERIOD => '1 hour',
					Parameters::DAYS => array('Mon', 'Wed', 'Fri', ),
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
					Parameters::DAYS => array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', ),
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
					Parameters::DAYS => array('Sat', 'Sun', ),
					Parameters::TIME => null
				), 'test04'
			),
		);
	}

}
