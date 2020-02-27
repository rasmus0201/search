<?php

require 'run.php';

use Search\Searcher;

$searcher = new Searcher('daen', ['da', 'en']);
$results = $searcher->results('klap lige hesten');
