<?php

require __DIR__ . '/../../../run.php';

use App\Dictionaries\Apollo\DocumentTransformer;
use Search\Indexing\Indexer;
use Search\Support\DatabaseConfig;

use App\Database\Database;
Database::run("DROP TABLE IF EXISTS info");
Database::run("DROP TABLE IF EXISTS term_has_inflections");
Database::run("DROP TABLE IF EXISTS inflections");
Database::run("DROP TABLE IF EXISTS term_index");
Database::run("DROP TABLE IF EXISTS document_index");

$config = new DatabaseConfig();
$config->setHost('localhost');
$config->setDatabase('search');
$config->setUsername('root');
$config->setPassword('');

$indexer = new Indexer(
    $config,
    new DocumentTransformer($config),
    new \Search\DefaultNormalizer(),
    new \Search\DefaultTokenizer()
);

$indexer->setQuery("
    SELECT e.`id`, e.`lemma_id`, e.`headword` as document FROM `entries` e
    WHERE e.`direction_id` IN (7, 8, 9, 10)
");

$indexer->run();
