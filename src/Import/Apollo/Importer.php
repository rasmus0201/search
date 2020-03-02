<?php

namespace Search\Import\Apollo;

use Search\Import\DatabaseImporterInterface;
use Search\Support\Config;
use Search\Support\DB;

class Importer implements DatabaseImporterInterface
{
    private $dbh;

    public function __construct(Config $config)
    {
        $this->setConnection($config);
    }

    public function setConnection(Config $config)
    {
        $this->dbh = (new DB($config))->getConnection();
    }

    public function import($toTableName)
    {

    }

    protected function parse()
    {
        // while (($line = fgets($this->handle)) !== false) {
        //     // How to do this?
        //
        //     yield $entry->jsonSerialize();
        // }
    }
}
