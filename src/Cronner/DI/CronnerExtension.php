<?php

declare(strict_types=1);

namespace stekycz\Cronner\DI;

use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Json;
use Nette\Utils\Validators;
use stekycz\Cronner\Bar\Tasks;
use stekycz\Cronner\CriticalSection;
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
		'tasks' => [],
		'bar' => '%debugMode%',
	];

	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		$config = $this->getConfig($this->defaults);
		Validators::assert($config['timestampStorage'], 'string|object|null', 'Timestamp storage definition');
		Validators::assert($config['maxExecutionTime'], 'integer|null', 'Script max execution time');
		Validators::assert($config['criticalSectionTempDir'], 'string', 'Critical section files directory path');

		$storage = $container->addDefinition($this->prefix('timestampStorage'))
            ->setAutowired(FALSE)
            ->setInject(FALSE);
		if (is_string($config['timestampStorage']) && $container->getServiceName($config['timestampStorage'])) {
			$storage->setFactory($config['timestampStorage']);
		} elseif (is_object($config['timestampStorage'])) {
			$storage->setClass($config['timestampStorage']->entity, $config['timestampStorage']->arguments);
		} else {
			$storageServiceName = $container->getByType(ITimestampStorage::class);
			if ($storageServiceName) {
				$storage->setFactory('@' . $storageServiceName);
			} else {
				$storage->setClass(self::DEFAULT_STORAGE_CLASS, [
					$container->expand(self::DEFAULT_STORAGE_DIRECTORY),
				]);
			}
		}

		$criticalSection = $container->addDefinition($this->prefix("criticalSection"))
             ->setClass(CriticalSection::class, [
                 $config['criticalSectionTempDir'],
             ])
             ->setAutowired(FALSE)
             ->setInject(FALSE);

		$runner = $container->addDefinition($this->prefix('runner'))
            ->setClass(Cronner::class, [
                $storage,
                $criticalSection,
                $config['maxExecutionTime'],
                array_key_exists('debugMode', $config) ? !$config['debugMode'] : TRUE,
            ]);

		Validators::assert($config['tasks'], 'array');
		foreach ($config['tasks'] as $task) {
			$def = $container->addDefinition($this->prefix('task.' . md5(Json::encode($task))));
			list($def->factory) = Compiler::filterArguments([
				is_string($task) ? new Statement($task) : $task,
			]);

			if (class_exists($def->factory->entity)) {
				$def->setClass($def->factory->entity);
			}

			$def->setAutowired(FALSE);
			$def->setInject(FALSE);
			$def->addTag(self::TASKS_TAG);
		}

		if ($config['bar'] && class_exists('Tracy\Bar')) {
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

}
