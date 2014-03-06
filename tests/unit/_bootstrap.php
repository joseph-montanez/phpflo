<?php
$autoloadFile = realpath(__DIR__.'/../../vendor/autoload.php');

if (!is_file($autoloadFile)) {
    throw new RuntimeException('Could not find autoloader. Did you run "composer install --dev"?');
}

$loader = require $autoloadFile;

$loader->add('Evenement\Tests', realpath(__DIR__."/../../vendor/evenement/evenement/tests"));

