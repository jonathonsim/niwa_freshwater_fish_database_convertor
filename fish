#!/usr/bin/env php7.3
<?php
require __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL);

$app = new Symfony\Component\Console\Application('NIWA Fish database convertor', '0.1');

//$app->add(new \App\Commands\ConfigureProxyCommand);
$app->add(new \App\Commands\ConvertFishDatabaseCommand());
$app->run();
