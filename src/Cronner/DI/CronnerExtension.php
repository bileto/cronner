<?php

declare(strict_types=1);

namespace stekycz\Cronner\DI;

use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Helpers;
use Nette\DI\ServiceDefinition;
use Nette\DI\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Json;
use Nette\Utils\Validators;
use stekycz\CriticalSection\CriticalSection;
use stekycz\CriticalSection\Driver\FileDriver;
use stekycz\CriticalSection\Driver\IDriver;
use stekycz\Cronner\Bar\Tasks;
use stekycz\Cronner\Cronner;
use stekycz\Cronner\ITimestampStorage;
use stekycz\Cronner\TimestampStorage\FileStorage;

class CronnerExtension extends CompilerExtension
{

	const TASKS_TAG = 'cronner.tasks';

	const DEFAULT_STORAGE_CLASS = FileStorage::class;
	const DEFAULT_STORAGE_DIRECTORY = '%tempDir%/cronner';

	/**
	 * @var array
	 */
	public $defaults = [
		'timestampStorage' => NULL,
		'maxExecutionTime' => NULL,
		'criticalSectionTempDir' => "%tempDir%/critical-section",
		'criticalSectionDriver' => NULL,
		'tasks' => [],
		'bar' => '%debugMode%',
	];

	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		$config = $this->getConfig($this->defaults);
		Validators::assert($config['timestampStorage'], 'string|object|null', 'Timestamp storage definition');
		Validators::assert($config['maxExecutionTime'], 'integer|null', 'Script max execution time');
		Validators::assert($config['criticalSectionTempDir'], 'string|null', 'Critical section files directory path (for critical section files driver only)');
		Validators::assert($config['criticalSectionDriver'], 'string|object|null', 'Critical section driver definition');

		$storage = $this->createServiceByConfig(
			$container,
			$this->prefix('timestampStorage'),
			$config['timestampStorage'],
			ITimestampStorage::class,
			self::DEFAULT_STORAGE_CLASS,
			[
				self::DEFAULT_STORAGE_DIRECTORY,
			]
		);

		$criticalSectionDriver = $this->createServiceByConfig(
			$container,
			$this->prefix('criticalSectionDriver'),
			$config['criticalSectionDriver'],
			IDriver::class,
			FileDriver::class,
			[
				$config['criticalSectionTempDir'],
			]
		);

		$criticalSection = $container->addDefinition($this->prefix("criticalSection"))
			 ->setFactory(CriticalSection::class, [
			 	$criticalSectionDriver,
			 ])
			 ->setAutowired(FALSE);

		$runner = $container->addDefinition($this->prefix('runner'))
			->setFactory(Cronner::class, [
				$storage,
				$criticalSection,
				$config['maxExecutionTime'],
				array_key_exists('debugMode', $config) ? !$config['debugMode'] : TRUE,
			]);

		if (isset($config['tasks'])) {
			Validators::assert($config['tasks'], 'array');
			foreach ($config['tasks'] as $task) {
				$def = $container->addDefinition($this->prefix('task.' . md5(is_string($task) ? $task : sprintf('%s-%s', $task->getEntity(), Json::encode($task)))));
				list($def->factory) = Compiler::filterArguments([
					is_string($task) ? new Statement($task) : $task,
				]);

				if (class_exists($def->factory->entity)) {
					$def->setFactory($def->factory->entity);
				}

				$def->setAutowired(FALSE);
				$def->addTag(self::TASKS_TAG);
			}
		}

		if (isset($config['bar']) && class_exists('Tracy\Bar')) {
			$container->addDefinition($this->prefix('bar'))
				->setClass(Tasks::class, [
					$this->prefix('@runner'),
					$this->prefix('@timestampStorage'),
				]);
		}
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$runner = $builder->getDefinition($this->prefix('runner'));
		foreach (array_keys($builder->findByTag(self::TASKS_TAG)) as $serviceName) {
			$runner->addSetup('addTasks', ['@' . $serviceName]);
		}
	}

	public function afterCompile(ClassType $class)
	{
		$builder = $this->getContainerBuilder();
		$init = $class->getMethod('initialize');

		if ($builder->hasDefinition($this->prefix('bar'))) {
			$init->addBody('$this->getByType(?)->addPanel($this->getService(?));', [
				'Tracy\Bar',
				$this->prefix('bar'),
			]);
		}
	}

	public static function register(Configurator $configurator)
	{
		$configurator->onCompile[] = function (Configurator $config, Compiler $compiler) {
			$compiler->addExtension('cronner', new CronnerExtension());
		};
	}

	private function createServiceByConfig(
		ContainerBuilder $container,
		string $serviceName,
		$config,
		string $fallbackType,
		string $fallbackClass,
		array $fallbackArguments
	) : ServiceDefinition
	{
		// @todo Nette 3 addFactoryDefinition
		if (is_string($config) && $container->findByType($config)) {
			$definition = $container->addFactoryDefinition($serviceName)
				->getResultDefinition()
				->setFactory($config);
		} elseif ($config instanceof Statement) {
			$definition = @$container->addDefinition($serviceName)
				->setClass($config->entity, $config->arguments);
		} else {
			$foundServiceName = @$container->getByType($fallbackType);
			if ($foundServiceName) {
				$definition = $container->addDefinition($serviceName)
					->setFactory('@' . $foundServiceName);
			} else {
				$definition = @$container->addDefinition($serviceName)
					->setClass($fallbackClass, Helpers::expand($fallbackArguments, $container->parameters));
			}
		}

		return $definition->setAutowired(FALSE);
	}

}
