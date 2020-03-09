<?php

namespace Search\Connectors;

use Search\Support\DatabaseConfig;

interface ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * @param DatabaseConfig $config
     * @return \PDO
     */
    public function connect(DatabaseConfig $config);
}
