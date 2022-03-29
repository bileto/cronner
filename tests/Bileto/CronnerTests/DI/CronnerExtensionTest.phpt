<?php

declare(strict_types=1);

namespace Bileto\CronnerTests\DI;

require_once(__DIR__ . '/../../../bootstrap.php');

use Bileto\CriticalSection\CriticalSection;
use Nette\Configurator;
use Nette\DI\Compiler;
use Bileto\Cronner\Cronner;
use Bileto\Cronner\DI\CronnerExtension;
use Bileto\Cronner\TimestampStorage\DummyStorage;
use Bileto\Cronner\TimestampStorage\FileStorage;
use Tester\Assert;
use Tester\Helpers;
use Tester\TestCase;

/**
 * @testCase
 */
class CronnerExtensionTest extends TestCase
{

	/** @var Compiler */
	private $compiler;

	public function testDefaultConfiguration(): void
	{
		$compiler = $this->compiler;
		$compiler->compile();

		$timestampStorage = $compiler->getContainerBuilder()->getDefinition('cronner.timestampStorage');
		$criticalSection = $compiler->getContainerBuilder()->getDefinition('cronner.criticalSection');
		$runner = $compiler->getContainerBuilder()->getDefinition('cronner.runner');

		Assert::same(FileStorage::class, $timestampStorage->getType());
		Assert::same(CriticalSection::class, $criticalSection->getType());
		Assert::same(Cronner::class, $runner->getType());
	}

	public function testCompleteConfiguration(): void
	{
		$compiler = $this->compiler;
		$compiler->getContainerBuilder()->addDefinition('cronner.dummyStorage')->setFactory(DummyStorage::class);
		$compiler->addConfig([
			'cronner' => [
				'timestampStorage' => DummyStorage::class,
				'maxExecutionTime' => 120,
				'criticalSectionTempDir' => __DIR__ . '../../../tmp/cronner',
			],
		]);
		$compiler->compile();

		$timestampStorage = $compiler->getContainerBuilder()->getDefinition('cronner.timestampStorage');
		$criticalSection = $compiler->getContainerBuilder()->getDefinition('cronner.criticalSection');
		$runner = $compiler->getContainerBuilder()->getDefinition('cronner.runner');

		Assert::same(DummyStorage::class, $timestampStorage->getType());
		Assert::same(CriticalSection::class, $criticalSection->getType());
		Assert::same(Cronner::class, $runner->getType());
	}

	public function testRegisterTasks(): void
	{
		Helpers::purge(__DIR__ . '/../../../tmp/');

		$config = new Configurator();
		$config->setTempDirectory(__DIR__ . '/../../../tmp/');
		$config->addConfig(__DIR__ . '/../../../config/config.neon');
		$container = $config->createContainer();

		$cronner = $container->getByType('Bileto\Cronner\Cronner');

		Assert::same(2, count($cronner->getTasks()));
	}

	protected function setUp(): void
	{
		parent::setUp();
		$this->compiler = new Compiler();
		$this->compiler->addConfig([
			'parameters' => [
				'appDir' => __DIR__ . '/../../..',
				'wwwDir' => __DIR__ . '/../../..',
				'tempDir' => TEMP_DIR,
				'debugMode' => false,
				'productionMode' => true,
			],
		]);
		$this->compiler->addExtension('cronner', new CronnerExtension());
	}
}

(new CronnerExtensionTest())->run();
