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

	/**
	 * @var array
	 */
	public $defaults = array(
		'timestampStorage' => NULL,
		'maxExecutionTime' => NULL,
	);



	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		$config = $this->getConfig($this->defaults);
		Validators::assert($config['maxExecutionTime'], 'integer|null', 'Script max execution time');

		$storage = $container->addDefinition($this->prefix('storage'));
		if (is_string($config['timestampStorage'])) {
			$storage->setClass($config['timestampStorage']);
		} else {
			$storage->setClass($config['timestampStorage']->value, $config['timestampStorage']->attributes);
		}

		$container->addDefinition($this->prefix('client'))
			->setClass('stekycz\Cronner\Cronner', array(
				$storage,
				$config['maxExecutionTime'],
				!$config['debugMode'],
			));
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
