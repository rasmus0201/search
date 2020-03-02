<?php

require 'run.php';

use Search\Import\Apollo\Importer;
use Search\Support\Config;

$config = new Config();
$config->setHost('localhost');
$config->setDatabase('search');
$config->setUsername('root');
$config->setPassword('');


echo 'Started importing Apollo datset' . PHP_EOL;

$importer = new Importer($config);
$importer->import('entries');

echo 'Finished importing Apollo dataset' . PHP_EOL;
