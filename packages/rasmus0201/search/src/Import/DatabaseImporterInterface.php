<?php

namespace Search\Import;

use Search\Support\DatabaseConfig;

interface DatabaseImporterInterface
{
    public function __construct(DatabaseConfig $config);
    public function setConnection(DatabaseConfig $config);
    public function import($toTableName);
}
