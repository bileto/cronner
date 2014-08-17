<?php

namespace stekycz\Cronner\DI;

use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\Utils\Validators;

if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
}



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class CronnerExtension extends CompilerExtension
{

	const TASKS_TAG = 'cronner.tasks';

	const DEFAULT_STORAGE_CLASS = 'stekycz\Cronner\TimestampStorage\FileStorage';
	const DEFAULT_STORAGE_DIRECTORY = '%wwwDir%/../temp/cronner';

	/**
	 * @var array
	 */
	public $defaults = array(
		'timestampStorage' => NULL,
		'maxExecutionTime' => NULL,
		'criticalSectionTempDir' => "%tempDir%/critical-section",
	);



	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		$config = $this->getConfig($this->defaults);
		Validators::assert($config['timestampStorage'], 'string|null', 'Timestamp storage definition');
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
				$storage->setClass(self::DEFAULT_STORAGE_CLASS, array(self::DEFAULT_STORAGE_DIRECTORY));
			}
		} else {
			if (is_string($config['timestampStorage']) && $container->getServiceName($config['timestampStorage'])) {
				$storage->setFactory($config['timestampStorage']);
			} else {
				$storage->setClass($config['timestampStorage']->value, $config['timestampStorage']->attributes);
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
				!$config['debugMode'],
			));

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
