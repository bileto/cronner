<?php

namespace stekycz\Cronner\tests\Tasks;

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

	/**
	 * @test
	 * @dataProvider dataProviderParseDays
	 * @param string $expected
	 * @param string $annotation
	 */
	public function parsesDays($expected, $annotation) {
		$this->assertEquals($expected, Parser::parseDays($annotation));
	}

	public function dataProviderParseDays() {
		return array(
			// Regular and simple values
			array(array('Mon', ), 'Mon'),
			array(array('Mon', 'Tue', ), 'Mon, Tue'),
			array(array('Mon', 'Fri', ), 'Mon, Fri'),
			array(array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', ), 'working days'),
			array(array('Sat', 'Sun', ), 'weekend'),
			// Day groups
			array(array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', ), 'working days, weekend'),
			array(array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', ), 'weekend, working days'),
			array(array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', ), 'working days, Mon'),
			array(array('Sat', 'Sun', ), 'weekend, Sat'),
			array(array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', ), 'Wed, working days'),
			array(array('Sat', 'Sun', ), 'Sat, weekend'),
			array(array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', ), 'Sat, working days'),
			array(array('Wed', 'Sat', 'Sun', ), 'Wed, weekend'),
			// Special cases (whitespaces)
			array(array('Mon', ), '   Mon   '),
			array(array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', ), '   working days   '),
			array(array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', ), '   Mon   ,   weekend   ,   working days   '),
		);
	}

	/**
	 * @test
	 * @expectedException \stekycz\Cronner\InvalidParameter
	 * @dataProvider dataProviderParseDaysError
	 * @param string $annotation
	 */
	public function throwsExceptionOnWrongDaysDefinition($annotation) {
		Parser::parseDays($annotation);
	}

	public function dataProviderParseDaysError() {
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

	/**
	 * @test
	 * @dataProvider dataProviderParseTimes
	 * @param string $expected
	 * @param string $annotation
	 */
	public function parsesTimes($expected, $annotation) {
		$this->assertEquals($expected, Parser::parseTimes($annotation));
	}

	public function dataProviderParseTimes() {
		return array(
			// Basic
			array(
				array(
					array(
						'from' => '11:00',
						'to' => null,
					),
				), '11:00',
			),
			array(
				array(
					array(
						'from' => '11:00',
						'to' => '12:00',
					),
				), '11:00 - 12:00',
			),
			// Multiple
			array(
				array(
					array(
						'from' => '11:00',
						'to' => null,
					),
					array(
						'from' => '17:00',
						'to' => null,
					),
				), '11:00, 17:00',
			),
			array(
				array(
					array(
						'from' => '11:00',
						'to' => '12:00',
					),
					array(
						'from' => '17:00',
						'to' => '19:00',
					),
				), '11:00 - 12:00, 17:00-19:00',
			),
			// Many whitespaces
			array(
				array(
					array(
						'from' => '11:00',
						'to' => '12:00',
					),
				), '    11:00     -     12:00    ',
			),
			array(
				array(
					array(
						'from' => '11:00',
						'to' => '12:00',
					),
					array(
						'from' => '17:00',
						'to' => '19:00',
					),
				), '   11:00   -   12:00   ,   17:00   -   19:00   ',
			),
			// Over midnight
			array(
				array(
					array(
						'from' => '00:00',
						'to' => '05:00',
					),
					array(
						'from' => '21:30',
						'to' => '23:59',
					),
				), '21:30 - 05:00',
			),
			// Critical
			array(
				array(
					array(
						'from' => '00:00',
						'to' => '05:00',
					),
					array(
						'from' => '16:00',
						'to' => '18:00',
					),
					array(
						'from' => '21:30',
						'to' => '23:59',
					),
				), '16:00 - 18:00, 21:30 - 05:00',
			),
			// Shortcuts
			array(
				array(
					array(
						'from' => '06:00',
						'to' => '11:59',
					),
				), 'morning',
			),
			array(
				array(
					array(
						'from' => '12:00',
						'to' => '12:29',
					),
				), 'noon',
			),
			array(
				array(
					array(
						'from' => '12:30',
						'to' => '16:59',
					),
				), 'afternoon',
			),
			array(
				array(
					array(
						'from' => '17:00',
						'to' => '21:59',
					),
				), 'evening',
			),
			array(
				array(
					array(
						'from' => '00:00',
						'to' => '05:59',
					),
					array(
						'from' => '22:00',
						'to' => '23:59',
					),
				), 'night',
			),
			array(
				array(
					array(
						'from' => '00:00',
						'to' => '00:29',
					),
				), 'midnight',
			),
			// Combined
			array(
				array(
					array(
						'from' => '00:00',
						'to' => '00:29',
					),
					array(
						'from' => '06:00',
						'to' => '11:59',
					),
				), 'morning, midnight',
			),
			array(
				array(
					array(
						'from' => '00:00',
						'to' => '00:29',
					),
					array(
						'from' => '03:00',
						'to' => '04:00',
					),
					array(
						'from' => '06:00',
						'to' => '11:59',
					),
				), 'morning, midnight, 03:00 - 04:00',
			),
		);
	}

	/**
	 * @test
	 * @expectedException \stekycz\Cronner\InvalidParameter
	 * @dataProvider dataProviderParseTimesError
	 * @param string $annotation
	 */
	public function throwsExceptionOnWrongTimesDefinition($annotation) {
		Parser::parseTimes($annotation);
	}

	public function dataProviderParseTimesError() {
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
