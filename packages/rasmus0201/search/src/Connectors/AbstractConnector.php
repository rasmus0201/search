<?php

namespace Search\Connectors;

use PDO;
use Search\Support\DatabaseConfig;

abstract class AbstractConnector
{
    /**
     * The default PDO connection options.
     *
     * @var mixed[]
     */
    private $options = [
        PDO::ATTR_CASE                     => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS             => PDO::NULL_NATURAL,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
        PDO::ATTR_STRINGIFY_FETCHES        => false,
        PDO::ATTR_EMULATE_PREPARES         => false,
    ];

    /**
     * Create a new PDO connection.
     *
     * @param string $dsn
     * @param DatabaseConfig $config
     * @param mixed[] $options
     * @return \PDO
     */
    public function createConnection($dsn, DatabaseConfig $config, array $options)
    {
        return new PDO(
            $dsn,
            $config->getUsername(),
            $config->getPassword(),
            $options
        );
    }

    /**
     * Get the default PDO connection options.
     *
     * @return mixed[]
     */
    public function getDefaultOptions()
    {
        return $this->options;
    }

    /**
     * Set the default PDO connection options.
     *
     * @param mixed[] $options
     * @return void
     */
    public function setDefaultOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Get the PDO options based on the configuration.
     *
     * @param DatabaseConfig $config
     * @return mixed[]
     */
    public function getOptions(DatabaseConfig $config)
    {
        $options = $this->options;

        foreach ($config->getPdoOptions() as $option => $value) {
            $options[$option] = $value;
        }

        return $options;
    }
}
