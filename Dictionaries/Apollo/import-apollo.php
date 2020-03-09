<?php

require __DIR__ . '/../run.php';

use Dictionaries\Apollo\Import\EntryImporter;
use Dictionaries\Apollo\Import\EntryLinker;
use Dictionaries\Apollo\Import\InflectionLinker;
use Dictionaries\Apollo\Import\LemmaImporter;
use Search\Support\DatabaseConfig;

$config = new DatabaseConfig();
$config->setHost('localhost');
$config->setDatabase('search');
$config->setUsername('root');
$config->setPassword('');

$startTime = date('Y-m-d H:i:s');
echo 'Started: ' . $startTime . PHP_EOL;

echo 'Started importing lemmas from Apollo dataset' . PHP_EOL;
$importer = new LemmaImporter($config);
$importer->setInflectionTable('lemma_inflections');
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

echo 'Started linking entries to lemmas from Apollo dataset' . PHP_EOL;
$importer = new EntryLinker($config);
$importer->link();
echo 'Finished linking entries to lemmas from Apollo dataset' . PHP_EOL;

$endTime = date('Y-m-d H:i:s');
echo 'Finished: ' . $endTime . PHP_EOL;

$diff = abs(strtotime($endTime) - strtotime($startTime));
echo 'Script took ' . round(($diff / 60), 2) . ' minutes' . PHP_EOL;
