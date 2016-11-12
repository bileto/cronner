<?php

namespace stekycz\Cronner\DI;

use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Json;
use Nette\Utils\Validators;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class CronnerExtension extends CompilerExtension
{

	const TASKS_TAG = 'cronner.tasks';

	const DEFAULT_STORAGE_CLASS = 'stekycz\Cronner\TimestampStorage\FileStorage';
	const DEFAULT_STORAGE_DIRECTORY = '%tempDir%/cronner';

	/**
	 * @var array
	 */
	public $defaults = array(
		'timestampStorage' => NULL,
		'maxExecutionTime' => NULL,
		'criticalSectionTempDir' => "%tempDir%/critical-section",
		'tasks' => array(),
		'bar' => '%debugMode%'
	);

	/**
	 * @param ContainerBuilder $containerBuilder
	 *
	 * @return \Nette\DI\ServiceDefinition
	 */
	protected function createTimestampStorage(ContainerBuilder $containerBuilder)
	{
		return $containerBuilder->addDefinition($this->prefix('timestampStorage'))
			->setAutowired(FALSE)
			->setInject(FALSE);
	}

	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		$config = $this->getConfig($this->defaults);
		Validators::assert($config['timestampStorage'], 'string|object|null', 'Timestamp storage definition');
		Validators::assert($config['maxExecutionTime'], 'integer|null', 'Script max execution time');
		Validators::assert($config['criticalSectionTempDir'], 'string', 'Critical section files directory path');


		if ($config['timestampStorage'] === NULL) {
			$storageServiceName = $container->getByType('stekycz\Cronner\ITimestampStorage');

			$storage = $this->createTimestampStorage($container);
			if ($storageServiceName) {
				$storage->setFactory('@' . $storageServiceName);
			} else {
				$storage->setClass(self::DEFAULT_STORAGE_CLASS, array(
					$container->expand(self::DEFAULT_STORAGE_DIRECTORY),
				));
			}
		} else {
			$storage = $this->createTimestampStorage($container);
			if (is_string($config['timestampStorage']) && $container->getServiceName($config['timestampStorage'])) {
				$storage->setFactory($config['timestampStorage']);
			} else {
				$storage->setClass($config['timestampStorage']->entity, $config['timestampStorage']->arguments);
			}
		}

		$criticalSection = $container->addDefinition($this->prefix("criticalSection"))
			->setClass('stekycz\Cronner\CriticalSection', array(
				$config['criticalSectionTempDir']
			))
			->setAutowired(FALSE)
			->setInject(FALSE);

		$runner = $container->addDefinition($this->prefix('runner'))
			->setClass('stekycz\Cronner\Cronner', array(
				$storage,
				$criticalSection,
				$config['maxExecutionTime'],
				array_key_exists('debugMode', $config) ? !$config['debugMode'] : TRUE,
			));

		Validators::assert($config['tasks'], 'array');
		foreach ($config['tasks'] as $task) {
			$def = $container->addDefinition($this->prefix('task.' . md5(Json::encode($task))));
			list($def->factory) = Compiler::filterArguments(array(
				is_string($task) ? new Statement($task) : $task
			));

			if (class_exists($def->factory->entity)) {
				$def->class = $def->factory->entity;
			}

			$def->setAutowired(FALSE);
			$def->setInject(FALSE);
			$def->addTag(self::TASKS_TAG);
		}

		if ($config['bar'] && class_exists('Tracy\Bar')) {
			$container->addDefinition($this->prefix('bar'))
				->setClass('stekycz\Cronner\Bar\Tasks', array($this->prefix('@runner'), $this->prefix('@timestampStorage')));
		}
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$runner = $builder->getDefinition($this->prefix('runner'));
		foreach (array_keys($builder->findByTag(self::TASKS_TAG)) as $serviceName) {
			$runner->addSetup('addTasks', array('@' . $serviceName));
		}
	}

	public function afterCompile(ClassType $class) {
		$builder = $this->getContainerBuilder();
		$init = $class->getMethod('initialize');

		if ($builder->hasDefinition($this->prefix('bar'))) {
			$init->addBody('$this->getByType(?)->addPanel($this->getService(?));', array('Tracy\Bar', $this->prefix('bar')));
		}
	}

	/**
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Configurator $configurator)
	{
		$configurator->onCompile[] = function (Configurator $config, Compiler $compiler) {
			$compiler->addExtension('cronner', new CronnerExtension());
		};
	}

}
