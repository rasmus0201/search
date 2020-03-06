<?php

require 'run.php';

use Search\DefaultNormalizer;
use Search\DefaultTokenizer;
use Search\Indexing\DefaultDocumentTransformer;
use Search\Indexing\Indexer;
use Search\Support\Config;
use Search\Support\StaticDB;

StaticDB::run("DROP TABLE IF EXISTS info");
StaticDB::run("DROP TABLE IF EXISTS term_index");
StaticDB::run("DROP TABLE IF EXISTS document_index");

// TODO
// Search with positional index using TF-IDF or BM25TP
// Stemming / Inflections?
// Stopword support?
// Fuzzy support?

$config = new Config();
$config->setHost('localhost');
$config->setDatabase('search');
$config->setUsername('root');
$config->setPassword('');

$indexer = new Indexer(
    $config,
    new DefaultDocumentTransformer(),
    new DefaultNormalizer(),
    new DefaultTokenizer()
);

$indexer->setQuery("
    SELECT e.`id`, e.`headword` as document FROM `entries` e
    WHERE e.`direction_id` IN (7, 8)
");

$indexer->run();
