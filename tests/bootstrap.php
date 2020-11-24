<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Autoloader.php';

$autoloader = Autoloader::getInstance();
$autoloader->addNamespace('PhpAmqpLib', dirname(__DIR__) . '/PhpAmqpLib');
$autoloader->register();