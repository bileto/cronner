<?php

declare(strict_types=1);

namespace stekycz\Cronner\DI;


use Bileto\CriticalSection\CriticalSection;
use Bileto\CriticalSection\Driver\FileDriver;
use Bileto\CriticalSection\Driver\IDriver;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\Extensions\InjectExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stekycz\Cronner\Bar\Tasks;
use stekycz\Cronner\Cronner;
use stekycz\Cronner\ITimestampStorage;
use stekycz\Cronner\TimestampStorage\FileStorage;
use Tracy\Bar;

final class CronnerExtension extends CompilerExtension
{
	private const TASKS_TAG = 'cronner.tasks';

	private const DEFAULT_STORAGE_DIRECTORY = '%tempDir%/cronner';


	public static function register(Configurator $configurator): void
	{
		$configurator->onCompile[] = static function (Configurator $config, Compiler $compiler): void {
			$compiler->addExtension('cronner', new CronnerExtension());
		};
	}


	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'assets' => Expect::arrayOf(Expect::string()),
			'timestampStorage' => Expect::string(),
			'maxExecutionTime' => Expect::int(),
			'criticalSectionTempDir' => Expect::string(),
			'criticalSectionDriver' => Expect::string(),
			'tasks' => Expect::array(),
			'bar' => Expect::bool(),
		])->castTo('array');
	}


	public function loadConfiguration(): void
	{
		/** @var mixed[] $config */
		$config = $this->config;
		$builder = $this->getContainerBuilder();

		$storage = $this->createServiceByConfig(
			$builder,
			$this->prefix('timestampStorage'),
			$config['timestampStorage'],
			ITimestampStorage::class,
			FileStorage::class, [
				self::DEFAULT_STORAGE_DIRECTORY,
			]
		);

		$criticalSectionDriver = $this->createServiceByConfig(
			$builder,
			$this->prefix('criticalSectionDriver'),
			$config['criticalSectionDriver'],
			IDriver::class,
			FileDriver::class, [
				$builder->parameters['tempDir'] . '/critical-section',
			]
		);

		$criticalSection = $builder->addDefinition($this->prefix("criticalSection"))
			->setFactory(CriticalSection::class, [$criticalSectionDriver])
			->setAutowired(false)
			->addTag(InjectExtension::TAG_INJECT, false);

		$builder->addDefinition($this->prefix('runner'))
			->setFactory(Cronner::class, [
				$storage,
				$criticalSection,
				$config['maxExecutionTime'],
				array_key_exists('debugMode', $config) ? !$config['debugMode'] : true,
			]);

		foreach ($config['tasks'] ?? [] as $task) {
			$def = $builder->addDefinition($this->prefix('task.' . md5(is_string($task) ? $task : $task->getEntity() . '-' . json_encode($task))));
			[$def->factory] = Compiler::filterArguments([
				is_string($task) ? new Statement($task) : $task,
			]);

			if (class_exists($def->factory->entity)) {
				$def->setFactory($def->factory->entity);
			}

			$def->setAutowired(false);
			$def->addTag(InjectExtension::TAG_INJECT, false);
			$def->addTag(self::TASKS_TAG);
		}

		if ($config['bar'] ?? false) {
			$builder->addDefinition($this->prefix('bar'))
				->setFactory(Tasks::class, [
					$this->prefix('@runner'),
					$this->prefix('@timestampStorage'),
				]);
		}
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		/** @var ServiceDefinition $runner */
		$runner = $builder->getDefinition($this->prefix('runner'));
		foreach (array_keys($builder->findByTag(self::TASKS_TAG)) as $serviceName) {
			$runner->addSetup('addTasks', ['@' . $serviceName]);
		}
	}


	public function afterCompile(ClassType $class): void
	{
		if ($this->getContainerBuilder()->hasDefinition($this->prefix('bar'))) {
			$class->getMethod('initialize')
				->addBody('$this->getByType(?)->addPanel($this->getService(?));', [
					Bar::class,
					$this->prefix('bar'),
				]);
		}
	}


	private function createServiceByConfig(ContainerBuilder $container, string $serviceName, $config, string $fallbackType, string $fallbackClass, array $fallbackArguments): Definition
	{
		if (is_string($config) && $container->getServiceName($config)) {
			$definition = $container->addDefinition($serviceName)
				->setFactory($config);
		} elseif ($config instanceof Statement) {
			$definition = $container->addDefinition($serviceName)
				->setFactory($config->entity, $config->arguments);
		} else {
			$foundServiceName = $container->getByType($fallbackType);
			if ($foundServiceName) {
				$definition = $container->addDefinition($serviceName)
					->setFactory('@' . $foundServiceName);
			} else {
				$definition = $container->addDefinition($serviceName)
					->setFactory($fallbackClass, $fallbackArguments);
			}
		}

		return $definition
			->setAutowired(false)
			->addTag(InjectExtension::TAG_INJECT, false);
	}
}
