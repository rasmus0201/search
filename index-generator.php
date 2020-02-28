<?php

require 'run.php';

// Look into teamtnt/tntsearch

use Search\DB;
use Search\DefaultTermNormalizer;
use Search\Indexing\DictccTransformer;
use Search\Indexing\Indexer;

$indexer = new Indexer(
    new DictccTransformer(),
    new DefaultTermNormalizer()
);

$entries = DB::run(
    "
        SELECT * FROM entries
        WHERE direction_id = :direction
        ORDER BY RAND()
        LIMIT :limit
    ",
    ['direction' => 2, 'limit' => 10]
)->fetchAll(\PDO::FETCH_ASSOC);

foreach ($indexer->index($entries) as $entryId => $indexItem) {
    echo 'Headword: ' .$indexItem->getDocument()['headword'] . '<br>';
    echo "<pre>    " . $indexItem->getTerm() .' '.$entryId.':'.$indexItem->getPosition() . "</pre>";
    echo '<br>';
    echo '<br>';
}
