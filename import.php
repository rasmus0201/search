<?php

require 'run.php';

use Search\Import\DictccImporter;

$fileName = DATA_PATH . '/dictcc-enda-test.txt';

$importer = new DictccImporter($fileName);

$entries = $importer->parse()->entries();
