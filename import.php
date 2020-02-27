<?php

require 'run.php';
require 'db.php';

use Search\Import\Dictcc\Importer;

$fileName = DATA_PATH . '/dictcc-enda-test.txt';
$directionId = 1;

$importer = new Importer($fileName);
list($valuesSql, $params) = $importer->parse($directionId)->toSql();

$stmt = DB::run("INSERT INTO entries VALUES " . $valuesSql, $params);
