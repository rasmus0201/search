<?php

namespace Search\Support;

use PDO;
use Search\Connectors\Traits\CanOpenConnections;
use Search\Support\Config;

class StaticDB
{
    use CanOpenConnections;

    /**
     * @var DB
     */
    private static $instance = null;

    /**
     * @var \PDO
     */
    private $dbh;

    private function __construct(Config $config)
    {
        $this->dbh = $this->createDatabaseHandle($this->config);
    }

    private function __clone() {}

    public static function instance(Config $config = null)
    {
        if (self::$instance === null)
        {
            $config = new Config();
            $config->setHost(getenv('DB_HOST'));
            $config->setDatabase(getenv('DB_NAME'));
            $config->setUsername(getenv('DB_USER'));
            $config->setPassword(getenv('DB_PASS'));

            self::$instance = new DB($config);
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
