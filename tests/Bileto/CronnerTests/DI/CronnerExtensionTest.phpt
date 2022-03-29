<?php

declare(strict_types=1);

namespace Bileto\CronnerTests\DI;

require_once(__DIR__ . '/../../../bootstrap.php');

use Bileto\CriticalSection\CriticalSection;
use Bileto\Cronner\ITimestampStorage;
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
	private Compiler $compiler;

	public function testDefaultConfiguration(): void
	{
		Helpers::purge(__DIR__ . '/../../../tmp/test-default-configuration');

		$config = new Configurator();
		$config->setTempDirectory(__DIR__ . '/../../../tmp/test-default-configuration');
		$config->addConfig(__DIR__ . '/../../../config/config-defaults.neon');
		$container = $config->createContainer();

		/** @var Cronner $cronner */
		$cronner = $container->getByType('Bileto\Cronner\Cronner');
		/** @var CriticalSection $criticalSection */
		$criticalSection = $container->getByType('Bileto\CriticalSection\CriticalSection');
		/** @var FileStorage $timestampStorage */
		$timestampStorage = $container->getByType('Bileto\Cronner\TimestampStorage\FileStorage');

		Assert::same(0, count($cronner->getTasks()));
		Assert::notNull($criticalSection);
		Assert::notNull($timestampStorage);
	}

	public function testCompleteConfiguration(): void
	{
		Helpers::purge(__DIR__ . '/../../../tmp/test-complete-configuration');

		$config = new Configurator();
		$config->setTempDirectory(__DIR__ . '/../../../tmp/test-complete-configuration');
		$config->addConfig(__DIR__ . '/../../../config/config-complete-configuration.neon');
		$container = $config->createContainer();

		/** @var Cronner $cronner */
		$cronner = $container->getByType('Bileto\Cronner\Cronner');
		/** @var CriticalSection $criticalSection */
		$criticalSection = $container->getByType('Bileto\CriticalSection\CriticalSection');
		/** @var ITimestampStorage $timestampStorage */
		$timestampStorage = $container->getByType('Bileto\Cronner\ITimestampStorage');

		Assert::same(0, count($cronner->getTasks()));
		Assert::same(120, $cronner->getMaxExecutionTime());
		Assert::notNull($criticalSection);
		Assert::type(DummyStorage::class, $timestampStorage);
	}

	public function testRegisterTasks(): void
	{
		Helpers::purge(__DIR__ . '/../../../tmp/test-register-tasks');

		$config = new Configurator();
		$config->setTempDirectory(__DIR__ . '/../../../tmp/test-register-tasks');
		$config->addConfig(__DIR__ . '/../../../config/config-with-tasks.neon');
		$container = $config->createContainer();

		$cronner = $container->getByType('Bileto\Cronner\Cronner');

		Assert::same(2, count($cronner->getTasks()));
	}

	protected function setUp(): void
	{
		parent::setUp();

		$this->compiler = $this->createCompiler();
	}

	/**
	 * @param array<mixed> $customConfig
	 */
	protected function createCompiler(array $customConfig = []): Compiler
	{
		$compiler = new Compiler();
		$compiler->addConfig(array_merge(
			[
				'parameters' => [
					'appDir' => __DIR__ . '/../../..',
					'wwwDir' => __DIR__ . '/../../..',
					'tempDir' => TEMP_DIR,
					'debugMode' => false,
					'productionMode' => true,
				],
			],
			$customConfig,
		));
		$compiler->addExtension('cronner', new CronnerExtension());

		return $compiler;
	}
}

(new CronnerExtensionTest())->run();
