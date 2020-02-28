<?php

namespace Search;

use PDO;

class DB
{
    protected static $instance = null;

    protected function __construct() {}
    protected function __clone() {}

    public static function instance()
    {
        if (self::$instance === null)
        {
            $opt = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => FALSE,
            ];
            $dsn = 'mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8';
            self::$instance = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), $opt);
        }
        return self::$instance;
    }

    public static function __callStatic($method, $args)
    {
        return call_user_func_array(array(self::instance(), $method), $args);
    }

    public static function run($sql, $args = [])
    {
        if (!$args){
            return self::instance()->query($sql);
        }

        $stmt = self::instance()->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }
}
