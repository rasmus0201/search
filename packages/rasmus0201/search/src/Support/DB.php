<?php

namespace Search\Support;

use Exception;
use PDO;
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

    public static function create(DatabaseConfig $config)
    {
        if ($config->getDriver() === 'sqlite') {
            return self::createSqlite($config);
        }

        return new self($config);
    }

    private static function createSqlite(DatabaseConfig $config)
    {
        if (empty($config->getDatabase())) {
            throw new Exception("Database must be set as a path for SQLite");
        }

        // Create SQLite database
        if (realpath($config->getDatabase()) === false) {
            $created = new PDO('sqlite:' . $config->getDatabase());
            $created = null;
        }

        return new self($config);
    }
}
