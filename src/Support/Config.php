<?php

namespace Search\Support;

class Config
{
    private $charset = 'utf8mb4';
    private $collation; // utf8_unicode_ci
    private $database;
    private $driver = 'mysql';
    private $host = 'localhost';
    private $modes = [];
    private $password;
    private $pdoOptions = [];
    private $port = 3306;
    private $socket = false;
    private $strict = false;
    private $timezone;
    private $username;

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    public function getCharset()
    {
        return $this->charset;
    }

    public function setCollation($collation)
    {
        $this->collation = $collation;
    }

    public function getCollation()
    {
        return $this->collation;
    }

    public function setDatabase($database)
    {
        $this->database = $database;
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function setDriver($driver)
    {
        $this->driver = $driver;
    }

    public function getDriver()
    {
        return $this->driver;
    }

    public function setHost($host)
    {
        $this->host = $host;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function setModes(array $modes)
    {
        $this->modes = $modes;
    }

    public function getModes()
    {
        return $this->modes;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPdoOptions(array $options)
    {
        $this->pdoOptions = $options;
    }

    public function getPdoOptions()
    {
        return $this->pdoOptions;
    }

    public function setPort($port)
    {
        $this->port = $port;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function setSocket($socket)
    {
        $this->socket = $socket;
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function setStrict($strict)
    {
        $this->strict = !!$strict;
    }

    public function getStrict()
    {
        return $this->strict;
    }

    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }
}
