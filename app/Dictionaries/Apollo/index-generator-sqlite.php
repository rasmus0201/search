<?php

require __DIR__ . '/../../../run.php';

use App\Dictionaries\Apollo\DocumentTransformer;
use Search\Indexing\Indexer;
use Search\Support\DatabaseConfig;

$queryDbh = new DatabaseConfig();
$queryDbh->setHost('localhost');
$queryDbh->setDatabase('search');
$queryDbh->setUsername('root');

$directions = [
    [
        'direction_id' => 7,
        'index_file' => ABS_PATH . '/storage/apollo.daen_rod.index',
    ],
    [
        'direction_id' => 8,
        'index_file' => ABS_PATH . '/storage/apollo.enda_rod.index',
    ],
    [
        'direction_id' => 9,
        'index_file' => ABS_PATH . '/storage/apollo.daen_stor.index',
    ],
    [
        'direction_id' => 10,
        'index_file' => ABS_PATH . '/storage/apollo.enda_stor.index',
    ],
];

foreach ($directions as $direction) {
    $config = new DatabaseConfig();
    $config->setDriver('sqlite');
    $config->setDatabase($direction['index_file']);

    $indexer = new Indexer(
        $config,
        new DocumentTransformer($queryDbh),
        new \Search\DefaultNormalizer(),
        new \Search\DefaultTokenizer()
    );

    $indexer->setQueryHandle($queryDbh);

    $indexer->setQuery("
        SELECT e.`id`, e.`lemma_id`, e.`headword` as document FROM `entries` e
        WHERE e.`direction_id` = :direction_id
    ", [
        ':direction_id' => $direction['direction_id']
    ]);

    $indexer->run();
}
