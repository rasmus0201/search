<?php

require 'run.php';

use Search\Import\Apollo\Importer;

$importer = new Importer();
$importer->import('test_documents');
