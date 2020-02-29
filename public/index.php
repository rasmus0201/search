<?php

require '../run.php';

$root = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'];

if (file_exists($root) && !in_array($_SERVER['REQUEST_URI'], ['/index.php', '/', ''])) {
    require __DIR__ . $_SERVER['REQUEST_URI'];
    die;
}

// use Search\Searcher;
//
// $searcher = new Searcher('daen', ['da', 'en']);
// $results = $searcher->results('klap lige hesten');
//
//
// dd($results);
?>
