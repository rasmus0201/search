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

// TODO
// Search with positional index using TF-IDF
// Stemming
// Inflections
// Stopword support
// Fuzzy support

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

$indexer->setQuery("
    SELECT d.`id`, d.`headword` as document FROM `documents` d
    WHERE d.`direction_id` IN (1, 2)
");
$indexer->run();
