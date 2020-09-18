<?php

declare(strict_types=1);

$autoloader = require_once __DIR__ . '/../../vendor/autoload.php';

define("TEST_DIR", __DIR__);
define("TEMP_DIR", TEST_DIR . '/../tmp/' . (isset($_SERVER['argv']) ? md5(serialize($_SERVER['argv'])) : getmypid()));

Tester\Environment::setup();

return $autoloader;
