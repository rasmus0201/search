<?php

require 'run.php';

use Search\Import\Apollo\Importer;
use Search\Support\Config;

$config = new Config();
$config->setHost('localhost');
$config->setDatabase('search');
$config->setUsername('root');
$config->setPassword('');

$importer = new Importer($config);
$importer->import('test_documents');
