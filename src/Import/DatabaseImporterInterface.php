<?php

namespace Search\Import;

use Search\Support\Config;

interface DatabaseImporterInterface
{
    public function __construct(Config $config);
    public function setConnection(Config $config);
    public function import($toTableName);
}
