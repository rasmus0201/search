<?php

namespace Search\Connectors;

use Search\Support\Config;

interface ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * @param Config $config
     * @return \PDO
     */
    public function connect(Config $config);
}
