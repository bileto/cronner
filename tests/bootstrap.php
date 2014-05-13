<?php

use Mockista\Registry;
use Nette\Utils\FileSystem;

$autoloader = require_once __DIR__ . '/../vendor/autoload.php';

define("TEST_DIR", __DIR__);
define("TEMP_DIR", TEST_DIR . '/tmp/' . getmypid());
FileSystem::createDir(TEMP_DIR);

function run(Tester\TestCase $testCase)
{
	$testCase->run(isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : NULL);
}



class TestCase extends Tester\TestCase
{

	/**
	 * @var \Mockista\Registry
	 */
	protected $mockista;



	protected function setUp()
	{
		$this->mockista = new Registry();
		usleep(1); // Hack for Mockista
	}



	protected function tearDown()
	{
		$this->mockista->assertExpectations();
	}

}

return $autoloader;
