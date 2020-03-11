<?php

require __DIR__ . '/../../../run.php';

use App\Dictionaries\Apollo\DocumentTransformer;
use Search\Indexing\Indexer;
use Search\Support\DatabaseConfig;

$queryDbh = new DatabaseConfig();
$queryDbh->setHost('localhost');
$queryDbh->setDatabase('search');
$queryDbh->setUsername('root');

$config = new DatabaseConfig();
$config->setDriver('sqlite');
$config->setDatabase(ABS_PATH . '/storage/apollo.daen_rod.index');

$indexer = new Indexer(
    $config,
    new DocumentTransformer($config),
    new \Search\DefaultNormalizer(),
    new \Search\DefaultTokenizer()
);

$indexer->setQueryHandle($queryDbh);

$indexer->setQuery("
    SELECT e.`id`, e.`lemma_id`, e.`headword` as document FROM `entries` e
    WHERE e.`direction_id` = 7
");

$indexer->run();
