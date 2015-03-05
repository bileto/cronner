<?php

/**
 * Test: stekycz\Cronner\DI\CronnerExtension
 *
 * @testCase stekycz\Cronner\tests\DI\CronnerExtensionTest
 */

namespace stekycz\Cronner\tests\DI;

use Nette;
use stekycz\Cronner\DI\CronnerExtension;
use Tester\Assert;



require_once(__DIR__ . "/../bootstrap.php");

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class CronnerExtensionTest extends \TestCase
{

	public function testDefaultConfiguration()
	{
		$compiler = new CompilerMock();
		$compiler->addExtension('cronner', $cronner = new CronnerExtension());

		$compiler->config = array();

		$cronner->loadConfiguration();

		$timestampStorage = $compiler->getContainerBuilder()->getDefinition('cronner.timestampStorage');
		$criticalSection = $compiler->getContainerBuilder()->getDefinition('cronner.criticalSection');
		$runner = $compiler->getContainerBuilder()->getDefinition('cronner.runner');

		Assert::same('stekycz\Cronner\TimestampStorage\FileStorage', $timestampStorage->class);
		Assert::same('stekycz\Cronner\CriticalSection', $criticalSection->class);
		Assert::same('stekycz\Cronner\Cronner', $runner->class);
	}



	public function testCompleteConfiguration()
	{
		$compiler = new CompilerMock();
		$compiler->addExtension('cronner', $cronner = new CronnerExtension());

		$compiler->config = array(
			'cronner' => array(
				'timestampStorage' => new Nette\DI\Statement('stekycz\Cronner\TimestampStorage\DummyStorage', array(TEMP_DIR . '/cronner')),
				'maxExecutionTime' => 120,
				'criticalSectionTempDir' => '%tempDir%/cronner',
			)
		);

		$cronner->loadConfiguration();

		$timestampStorage = $compiler->getContainerBuilder()->getDefinition('cronner.timestampStorage');
		$criticalSection = $compiler->getContainerBuilder()->getDefinition('cronner.criticalSection');
		$runner = $compiler->getContainerBuilder()->getDefinition('cronner.runner');

		Assert::same('stekycz\Cronner\TimestampStorage\DummyStorage', $timestampStorage->class);
		Assert::same('stekycz\Cronner\CriticalSection', $criticalSection->class);
		Assert::same('stekycz\Cronner\Cronner', $runner->class);
	}

}



class CompilerMock extends Nette\Config\Compiler
{

	/**
	 * @var Nette\DI\ContainerBuilder
	 */
	public $containerBuilder;

	/**
	 * @var array
	 */
	public $config = array();



	public function __construct()
	{
		$this->containerBuilder = new Nette\DI\ContainerBuilder();
		$this->containerBuilder->parameters = array(
			'appDir' => __DIR__ . '/../..',
			'wwwDir' => __DIR__ . '/../..',
			'tempDir' => TEMP_DIR,
			'debugMode' => FALSE,
			'productionMode' => TRUE,
		);
	}



	/**
	 * @return array
	 */
	public function getConfig()
	{
		return $this->config;
	}



	/**
	 * @return Nette\DI\ContainerBuilder
	 */
	public function getContainerBuilder()
	{
		return $this->containerBuilder;
	}

}



run(new CronnerExtensionTest());
