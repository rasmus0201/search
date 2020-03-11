<?php

namespace Search\Connectors;

use Exception;
use PDO;
use Search\Support\DatabaseConfig;

class SQLiteConnector extends AbstractConnector implements ConnectorInterface
{
    /**
     * @inheritdoc
     *
     * @throws \InvalidArgumentException
     */
    public function connect(DatabaseConfig $config)
    {
        $this->setDefaultOptions([]);
        $options = $this->getOptions($config);
        $database = $config->getDatabase();

        // SQLite supports "in-memory" databases that only last as long as the owning
        // connection does. These are useful for tests or for short lifetime store
        // querying. In-memory databases may only have a single open connection.
        if ($database == ':memory:') {
            return $this->createConnection("sqlite::memory:", $config, $options);
            return new PDO('sqlite::memory:');
        }

        $path = realpath($database);

        // Here we'll verify that the SQLite database exists before going any further
        // as the developer probably wants to know if the database exists and this
        // SQLite driver will not throw any exception if it does not by default.
        if ($path === false) {
            throw new Exception("Database ({$database}) does not exist.");
        }

        return $this->createConnection("sqlite:{$path}", $config, $options);
        return new PDO("sqlite:{$path}");
    }
}
