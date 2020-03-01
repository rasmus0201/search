<?php

namespace Search\Connectors\Traits;

use Search\Connectors\MySqlConnector;
use Search\Connectors\SQLiteConnector;
use Search\Support\Config;

trait CanOpenConnections
{
    private function createDatabaseHandle(Config $config)
    {
        $connector = $this->createConnector($config);

        return $connector->connect($config);
    }

    private function createConnector(Config $config)
    {
        $map = [
            'mysql' => MySqlConnector::class,
            'sqlite' => SQLiteConnector::class,
        ];

        $driver = $config->getDriver();

        if (!isset($map[$driver])) {
            throw new Exception("Unsupported driver [{$driver}]");
        }

        return new $map[$driver]();
    }
}
