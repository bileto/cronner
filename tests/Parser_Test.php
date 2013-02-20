<?php

namespace stekycz\Cronner\tests;

use PHPUnit_Framework_TestCase;
use stdClass;
use stekycz\Cronner\Tasks\Parser;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-05
 */
class Parser_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @test
	 * @dataProvider dataProviderParseName
	 * @param string $expected
	 * @param string $annotation
	 */
	public function parsesName($expected, $annotation) {
		$this->assertEquals($expected, Parser::parseName($annotation));
	}

	public function dataProviderParseName() {
		return array(
			array('Testovací úkol', 'Testovací úkol'),
			array('Testovací úkol', '   Testovací úkol   '),
			array('true', 'true'),
			array('false', 'false'),
			array('0', '0'),
			array('1', '1'),
			array(null, true),
			array(null, false),
			array(null, 0),
			array(null, 1),
			array(null, new stdClass()),
		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderParsePeriod
	 * @param string $expected
	 * @param string $annotation
	 */
	public function parsesPeriod($expected, $annotation) {
		$this->assertEquals($expected, Parser::parsePeriod($annotation));
	}

	public function dataProviderParsePeriod() {
		return array(
			array('5 minutes', '5 minutes'),
			array('5 minutes', '   5 minutes   '),
			array('2 seconds', '2 seconds'),
			array('2 weeks', '2 weeks'),
			array('2 months', '2 months'),
		);
	}

	/**
	 * @test
	 * @expectedException \stekycz\Cronner\InvalidParameter
	 * @dataProvider dataProviderParsePeriodError
	 * @param string $annotation
	 */
	public function throwsExceptionOnWrongPeriodDefinition($annotation) {
		Parser::parsePeriod($annotation);
	}

	public function dataProviderParsePeriodError() {
		return array(
			array('nejaky blabol'),
			array('true'),
			array('false'),
			array('0'),
			array('1'),
			array(true),
			array(false),
			array(0),
			array(1),
			array(new stdClass()),
		);
	}

}
