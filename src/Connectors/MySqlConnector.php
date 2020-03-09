<?php

namespace Search\Connectors;

use PDO;
use Search\Support\DatabaseConfig;

class MySqlConnector extends AbstractConnector implements ConnectorInterface
{
    /**
     * @inheritdoc
     */
    public function connect(DatabaseConfig $config)
    {
        $dsn = $this->getDsn($config);
        $options = $this->getOptions($config);

        // We need to grab the PDO options that should be used while making the brand
        // new connection instance. The PDO options control various aspects of the
        // connection's behavior, and some might be specified by the developers.
        $connection = $this->createConnection($dsn, $config, $options);

        // Next we will set the "names" and "collation" on the clients connections so
        // a correct character set will be used by this client. The collation also
        // is set on the server but needs to be set here on this client objects.
        if ($charset = $config->getCharset()) {
            if ($collation = $config->getCollation()) {
                $connection->exec("SET NAMES '{$charset}' COLLATE '{$collation}'");
            } else {
                $connection->exec("SET NAMES '{$charset}'");
            }
        }

        // Next, we will check to see if a timezone has been specified in this config
        // and if it has we will issue a statement to modify the timezone with the
        // database. Setting this DB timezone is an optional configuration item.
        if ($timezone = $config->getTimezone()) {
            $connection->prepare('SET TIME_ZONE = :timezone')->execute([
                'timezone' => $timezone,
            ]);
        }

        $this->setModes($connection, $config);

        return $connection;
    }

    /**
     * Create a DSN string from a configuration.
     *
     * Chooses socket or host/port based on the 'unix_socket' config value.
     *
     * @param DatabaseConfig $config
     * @return string
     */
    private function getDsn(DatabaseConfig $config)
    {
        return $this->configHasSocket($config) ? $this->getSocketDsn($config) : $this->getHostDsn($config);
    }

    /**
     * Determine if the given configuration array has a UNIX socket value.
     *
     * @param DatabaseConfig $config
     * @return bool
     */
    private function configHasSocket(DatabaseConfig $config)
    {
        return $config->getSocket() !== false && trim($config->getSocket()) !== '';
    }

    /**
     * Get the DSN string for a socket configuration.
     *
     * @param DatabaseConfig $config
     * @return string
     */
    private function getSocketDsn(DatabaseConfig $config)
    {
        return "mysql:unix_socket={$config->getsocket()};dbname={$config->getDatabase()}";
    }

    /**
     * Get the DSN string for a host / port configuration.
     *
     * @param DatabaseConfig $config
     * @return string
     */
    private function getHostDsn(DatabaseConfig $config)
    {
        if ($port = $config->getPort()) {
            return "mysql:host={$config->getHost()};port={$port};dbname={$config->getDatabase()}";
        }

        return "mysql:host={$config->getHost()};dbname={$config->getDatabase()}";
    }

    /**
     * Set the modes for the connection.
     *
     * @param \PDO $connection
     * @param DatabaseConfig $config
     * @return void
     */
    private function setModes(PDO $connection, DatabaseConfig $config)
    {
        if ($modes = $config->getModes()) {
            $modes = implode(',', $modes);
            $connection->prepare("SET SESSION sql_mode='{$modes}'")->execute();

            return;
        }

        if (is_null($config->getStrict())) {
            return;
        }

        if ($config->getStrict()) {
            $connection->prepare("SET SESSION sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'")->execute();
        } else {
            $connection->prepare("SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
        }
    }
}
