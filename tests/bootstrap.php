<?php

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-03
 */

use Nette\Config\Configurator;

define('TEST_DIR', __DIR__);
define('LIBS_DIR', TEST_DIR . '/../vendor');

// Composer autoloading
require LIBS_DIR . '/autoload.php';

// Configure application
$configurator = new Configurator();

// Enable Nette Debugger for error visualisation & logging
$configurator->setDebugMode();
$configurator->enableDebugger(TEST_DIR . '/log', 'martin.stekl@gmail.com');

// Enable RobotLoader - this will load all classes automatically
$configurator->setTempDirectory(TEST_DIR . '/temp');

$configurator->createRobotLoader()
	->addDirectory(TEST_DIR)
	->register();
