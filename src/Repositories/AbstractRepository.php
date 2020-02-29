<?php

namespace Search\Repositories;

use PDO;

abstract class AbstractRepository
{
    /**
     * @var \PDO
     */
    protected $dbh;

    public function __construct(PDO $databaseHandle)
    {
        $this->dbh = $databaseHandle;
    }

    // use Search\Connectors\MysqlConnector;
    // use Search\Connectors\SQLiteConnector;
    // use Search\Support\Config;

    // public function __construct(Config $config)
    // {
    //     $this->dbh = $this->createDatabaseHandle($config);
    // }

    // private function createDatabaseHandle(Config $config)
    // {
    //     $connector = $this->createConnector($config);
    //
    //     return $connector->connect($config);
    // }
    //
    // private function createConnector(Config $config)
    // {
    //     $map = [
    //         'mysql' => MySqlConnector::class,
    //         'sqlite' => SQLiteConnector::class,
    //     ];
    //
    //     $driver = $config->getDriver();
    //
    //     if (!isset($map[$driver])) {
    //         throw new Exception("Unsupported driver [{$driver}]");
    //     }
    //
    //     return new $map[$driver]();
    // }
}
