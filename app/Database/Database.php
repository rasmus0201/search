<?php

namespace App\Database;

use Search\Connectors\Traits\CanOpenConnections;
use Search\Support\DatabaseConfig;

class Database
{
    use CanOpenConnections;

    /**
     * @var Database
     */
    private static $instance = null;

    /**
     * @var \PDO
     */
    private $dbh;

    private function __construct(DatabaseConfig $config)
    {
        $this->dbh = $this->createDatabaseHandle($config);
    }

    private function __clone() {}

    public static function instance(DatabaseConfig $config = null)
    {
        if (self::$instance === null)
        {
            $config = new DatabaseConfig();
            $config->setDriver(getenv('DB_CONNECTION'));
            $config->setHost(getenv('DB_HOST'));
            $config->setPort(getenv('DB_PORT'));
            $config->setDatabase(getenv('DB_DATABASE'));
            $config->setUsername(getenv('DB_USERNAME'));
            $config->setPassword(getenv('DB_PASSWORD'));

            self::$instance = new Database($config);
        }

        return self::$instance;
    }

    public static function __callStatic($method, $args)
    {
        return call_user_func_array([
            self::instance()->dbh,
            $method
        ], $args);
    }

    public static function run($sql, $args = [])
    {
        if (!$args) {
            return self::instance()->dbh->query($sql);
        }

        $stmt = self::instance()->dbh->prepare($sql);
        $stmt->execute($args);

        return $stmt;
    }
}
