<?php

namespace Search\Support;

use Search\Connectors\Traits\CanOpenConnections;
use Search\Support\Config;

class DB
{
    use CanOpenConnections;

    /**
     * @var \PDO
     */
    private $dbh;

    public function __construct(Config $config)
    {
        $this->dbh = $this->createDatabaseHandle($config);
    }

    public function getConnection()
    {
        return $this->dbh;
    }
}
