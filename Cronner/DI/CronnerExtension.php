<?php

namespace stekycz\Cronner\DI;

use Nette\Config\Compiler;
use Nette\Config\CompilerExtension;
use Nette\Config\Configurator;
use Nette\Utils\Validators;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-03-18
 */
class CronnerExtension extends CompilerExtension {

	/**
	 * @var array
	 */
	public $defaults = array(
		'timestampStorage' => null,
		'maxExecutionTime' => null,
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
	 * @param \Nette\Config\Configurator $configurator
	 */
	public static function register(Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Compiler $compiler) {
			$compiler->addExtension('cronner', new CronnerExtension());
		};
	}

}
