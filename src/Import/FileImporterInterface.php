<?php

namespace Search\Import;

use Search\Support\DatabaseConfig;

interface FileImporterInterface
{
    public function __construct($filePath, $directionId);
    public function setConnection(DatabaseConfig $config);
    public function import($toTableName);
}
