<?php

require 'run.php';

use Search\DefaultNormalizer;
use Search\DefaultTokenizer;
use Search\Indexing\DictccTransformer;
use Search\Indexing\Indexer;
use Search\Support\Config;

Search\DB::run("DROP TABLE IF EXISTS info");
Search\DB::run("DROP TABLE IF EXISTS term_index");
Search\DB::run("DROP TABLE IF EXISTS document_index");
sleep(1);

$config = new Config();
$config->setHost('localhost');
$config->setDatabase('search');
$config->setUsername('root');
$config->setPassword('');

$indexer = new Indexer(
    $config,
    new DictccTransformer(),
    new DefaultNormalizer(),
    new DefaultTokenizer()
);

$indexer->query("
    SELECT e.`id`, e.`headword` FROM `entries` e
    LIMIT :limit
", ['limit' => 100000]);


$indexer->run();
