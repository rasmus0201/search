<?php

namespace Search\Import;

use Search\Import\Traits\CanInsertMultipleValuesMysql;
use Search\Support\DatabaseConfig;
use Search\Support\DB;

abstract class AbstractFileImporter
{
    use CanInsertMultipleValuesMysql;

    const CHUNK_LIMIT = 500;

    protected $lines;
    protected $rows;
    protected $directionId;
    protected $handle;
    protected $dbh;

    abstract protected function parse();

    public function __construct($filePath, $directionId)
    {
        $this->directionId = $directionId;
        $this->rows = [];
        $this->handle = fopen($filePath, 'r');

        if (!$this->handle) {
            throw new \Exception('Could not open file');
        }
    }

    public function __destruct()
    {
        if (!$this->handle) {
            return;
        }

        fclose($this->handle);
    }

    public function setConnection(DatabaseConfig $config)
    {
        $this->dbh = (new DB($config))->getConnection();
    }

    public function import($toTableName)
    {
        $rows = [];
        foreach ($this->parse() as $entry) {
            $rows[] = $entry;

            if (count($rows) === self::CHUNK_LIMIT) {
                $this->performInsert($toTableName, $rows);

                $rows = [];
            }
        }

        // If the last run is less than chunk, there will be remaining rows.
        if (count($rows) > 0) {
            $this->performInsert($toTableName, $rows);
        }
    }
}
