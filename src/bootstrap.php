<?php

use Symla\Joomla\Application;
use Symla\Joomla\Environment\Environment;
use Symla\Joomla\Environment\Loader;


// require_once('path/to/autoloader');

$loader      = new Loader();
$environment = new Environment($loader->load('path/to/.env'));

if ($environment->environment() !== $environment::PRODUCTION) {
    Symla\Joomla\Tracy\Debugger::enable();
}

Application::instantiate($environment);
$application = Application::getInstance();
