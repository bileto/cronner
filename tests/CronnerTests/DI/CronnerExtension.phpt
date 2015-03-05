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

	/**
	 * @var Nette\DI\Compiler
	 */
	private $compiler;



	protected function setUp()
	{
		parent::setUp();
		$builder = new Nette\DI\ContainerBuilder();
		$builder->parameters = array(
			'appDir' => __DIR__ . '/../..',
			'wwwDir' => __DIR__ . '/../..',
			'tempDir' => TEMP_DIR,
			'debugMode' => FALSE,
			'productionMode' => TRUE,
		);
		$this->compiler = new Nette\DI\Compiler($builder);
		$this->compiler->addExtension('cronner', new CronnerExtension());
	}



	public function testDefaultConfiguration()
	{
		$compiler = $this->compiler;
		$compiler->compile(array(), 'Container', 'Nette\DI\Container');

		$timestampStorage = $compiler->getContainerBuilder()->getDefinition('cronner.timestampStorage');
		$criticalSection = $compiler->getContainerBuilder()->getDefinition('cronner.criticalSection');
		$runner = $compiler->getContainerBuilder()->getDefinition('cronner.runner');

		Assert::same('stekycz\Cronner\TimestampStorage\FileStorage', $timestampStorage->getClass());
		Assert::same('stekycz\Cronner\CriticalSection', $criticalSection->getClass());
		Assert::same('stekycz\Cronner\Cronner', $runner->getClass());
	}



	public function testCompleteConfiguration()
	{
		$compiler = $this->compiler;
		$compiler->compile(array(
			'cronner' => array(
				'timestampStorage' => new Nette\DI\Statement('stekycz\Cronner\TimestampStorage\DummyStorage'),
				'maxExecutionTime' => 120,
				'criticalSectionTempDir' => '%tempDir%/cronner',
			)
		), 'Container', 'Nette\DI\Container');

		$timestampStorage = $compiler->getContainerBuilder()->getDefinition('cronner.timestampStorage');
		$criticalSection = $compiler->getContainerBuilder()->getDefinition('cronner.criticalSection');
		$runner = $compiler->getContainerBuilder()->getDefinition('cronner.runner');

		Assert::same('stekycz\Cronner\TimestampStorage\DummyStorage', $timestampStorage->getClass());
		Assert::same('stekycz\Cronner\CriticalSection', $criticalSection->getClass());
		Assert::same('stekycz\Cronner\Cronner', $runner->getClass());
	}

}



run(new CronnerExtensionTest());
