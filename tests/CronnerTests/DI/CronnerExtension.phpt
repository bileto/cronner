<?php

/**
 * @testCase
 */

namespace stekycz\Cronner\tests\DI;

use Nette\DI\Compiler;
use Nette\DI\ContainerBuilder;
use Nette\DI\Statement;
use stekycz\Cronner\CriticalSection;
use stekycz\Cronner\Cronner;
use stekycz\Cronner\DI\CronnerExtension;
use stekycz\Cronner\TimestampStorage\DummyStorage;
use stekycz\Cronner\TimestampStorage\FileStorage;
use Tester\Assert;

require_once(__DIR__ . "/../bootstrap.php");

class CronnerExtensionTest extends \TestCase
{

	/**
	 * @var Compiler
	 */
	private $compiler;

	protected function setUp()
	{
		parent::setUp();
		$builder = new ContainerBuilder();
		$builder->parameters = [
			'appDir' => __DIR__ . '/../..',
			'wwwDir' => __DIR__ . '/../..',
			'tempDir' => TEMP_DIR,
			'debugMode' => FALSE,
			'productionMode' => TRUE,
		];
		$this->compiler = new Compiler($builder);
		$this->compiler->addExtension('cronner', new CronnerExtension());
	}

	public function testDefaultConfiguration()
	{
		$compiler = $this->compiler;
		$compiler->compile();

		$timestampStorage = $compiler->getContainerBuilder()->getDefinition('cronner.timestampStorage');
		$criticalSection = $compiler->getContainerBuilder()->getDefinition('cronner.criticalSection');
		$runner = $compiler->getContainerBuilder()->getDefinition('cronner.runner');

		Assert::same(FileStorage::class, $timestampStorage->getClass());
		Assert::same(CriticalSection::class, $criticalSection->getClass());
		Assert::same(Cronner::class, $runner->getClass());
	}

	public function testCompleteConfiguration()
	{
		$compiler = $this->compiler;
		$compiler->addConfig([
			'cronner' => [
				'timestampStorage' => new Statement('stekycz\Cronner\TimestampStorage\DummyStorage'),
				'maxExecutionTime' => 120,
				'criticalSectionTempDir' => '%tempDir%/cronner',
			],
		]);
		$compiler->compile();

		$timestampStorage = $compiler->getContainerBuilder()->getDefinition('cronner.timestampStorage');
		$criticalSection = $compiler->getContainerBuilder()->getDefinition('cronner.criticalSection');
		$runner = $compiler->getContainerBuilder()->getDefinition('cronner.runner');

		Assert::same(DummyStorage::class, $timestampStorage->getClass());
		Assert::same(CriticalSection::class, $criticalSection->getClass());
		Assert::same(Cronner::class, $runner->getClass());
	}

}

run(new CronnerExtensionTest());
