<?php

namespace Search\Import;

use Search\DB;

abstract class AbstractImporter
{
    protected $lines;
    protected $rows;
    protected $directionId;
    protected $handle;

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

    public function import($tableName)
    {
        $rows = [];
        foreach ($this->parse() as $entry) {
            $rows[] = $entry;

            if (count($rows) === 500) {
                $this->performInsert($tableName, $rows);

                $rows = [];
            }
        }

        // If the last run is less than 500, there will be remaining rows.
        if (count($rows) > 0) {
            $this->performInsert($tableName, $rows);
        }
    }

    private function performInsert($tableName, $rows)
    {
        $insertSql = $this->generateInsertSql($tableName, $rows);
        $updateSql = $this->generateUpdateSql(array_keys($rows[0]));

        try {
            $stmt = DB::run($insertSql.$updateSql, $this->generateBindings($rows));
        } catch (\Exception $e) {
            $stmt = false;
        }

        if (!$stmt) {
            echo 'An error happened during import into database' . PHP_EOL;
        }
    }

    private function generateBindings(array $rows)
    {
        return call_user_func_array('array_merge', array_map('array_values', $rows));
    }

    private function generateInsertSql($tableName, array $rows)
    {
        $values = [];
        $index = 0;
        foreach ($rows as $row) {
            $value = [];

            foreach ($row as $column) {
                $value[] = ':'.$index++;
            }

            $values[] = '('.implode(', ', $value).')';
        }

        return 'INSERT INTO `'.$tableName.'` VALUES ' . implode(', ', $values);
    }

    private function generateUpdateSql($columns)
    {
        $updates = [];
        foreach ($columns as $column) {
            $updates[] = sprintf('`%s` = VALUES(`%s`)', $column, $column);
        }

        return ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    }
}
