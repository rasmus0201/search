<?php

namespace Search\Import;

use Search\Support\Config;

interface FileImporterInterface
{
    public function __construct($filePath, $directionId);
    public function setConnection(Config $config);
    public function import($toTableName);
}
