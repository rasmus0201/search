<?php

require 'run.php';

use Search\Import\Apollo\EntryImporter;
use Search\Import\Apollo\InflectionLinker;
use Search\Import\Apollo\LemmaImporter;
use Search\Support\Config;

$config = new Config();
$config->setHost('localhost');
$config->setDatabase('search');
$config->setUsername('root');
$config->setPassword('');


echo 'Started importing lemmas from Apollo dataset' . PHP_EOL;
$importer = new LemmaImporter($config);
$importer->setInflectionTable('inflections');
$importer->import('lemmas');
echo 'Finished importing lemmas from Apollo dataset' . PHP_EOL;

echo 'Started linking inflections to lemmas from Apollo dataset' . PHP_EOL;
$importer = new InflectionLinker($config);
$importer->link();
echo 'Finished linking inflections to lemmas from Apollo dataset' . PHP_EOL;

echo 'Started entries importing Apollo dataset' . PHP_EOL;
$importer = new EntryImporter($config);
$importer->import('entries');
echo 'Finished entries importing Apollo dataset' . PHP_EOL;

// TODO Set entries lemma_id to the correct one.
