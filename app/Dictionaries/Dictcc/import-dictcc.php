<?php

require __DIR__ . '/../../../run.php';

use App\Dictionaries\Dictcc\Import\Importer;
use Search\Support\DatabaseConfig;

$imports = [
    [
        'filename' => DATA_PATH . '/dictcc-daen.txt',
        'direction_id' => 1,
    ],
    [
        'filename' => DATA_PATH . '/dictcc-enda.txt',
        'direction_id' => 2,
    ],
    [
        'filename' => DATA_PATH . '/dictcc-dade.txt',
        'direction_id' => 3,
    ],
    [
        'filename' => DATA_PATH . '/dictcc-deda.txt',
        'direction_id' => 4,
    ],
    [
        'filename' => DATA_PATH . '/dictcc-ende.txt',
        'direction_id' => 5,
    ],
    [
        'filename' => DATA_PATH . '/dictcc-deen.txt',
        'direction_id' => 6,
    ],
];

$config = new DatabaseConfig();
$config->setHost('localhost');
$config->setDatabase('search');
$config->setUsername('root');
$config->setPassword('');

foreach ($imports as $import) {
    $fileName = $import['filename'];

    echo 'Started importing '. $fileName . PHP_EOL;

    $importer = new Importer($fileName, $import['direction_id']);
    $importer->setConnection($config);
    $importer->import('documents');

    echo 'Finished importing '. $fileName . PHP_EOL;
}
