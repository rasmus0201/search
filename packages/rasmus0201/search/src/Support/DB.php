<?php

namespace Search\Support;

use Search\Connectors\Traits\CanOpenConnections;
use Search\Support\DatabaseConfig;

class DB
{
    use CanOpenConnections;

    /**
     * @var \PDO
     */
    private $dbh;

    public function __construct(DatabaseConfig $config)
    {
        $this->dbh = $this->createDatabaseHandle($config);
    }

    public function getConnection()
    {
        return $this->dbh;
    }
}
