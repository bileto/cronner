<?php

namespace stekycz\Cronner\DI;

use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
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
	);



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
		if ($config['timestampStorage'] === NULL) {
			$storageServiceName = $container->getByType('stekycz\Cronner\ITimestampStorage');
			if ($storageServiceName) {
				$storage->setFactory('@' . $storageServiceName);
			} else {
				$storage->setClass(self::DEFAULT_STORAGE_CLASS, array(
					$container->expand(self::DEFAULT_STORAGE_DIRECTORY),
				));
			}
		} else {
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

		foreach (array_keys($container->findByTag(self::TASKS_TAG)) as $serviceName) {
			$runner->addSetup('addTasks', array('@' . $serviceName));
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
