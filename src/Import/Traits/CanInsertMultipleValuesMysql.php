<?php

namespace Search\Import\Traits;

trait CanInsertMultipleValuesMysql
{
    private function performInsert($tableName, $rows)
    {
        $insertSql = $this->generateInsertSql($tableName, $rows);
        $updateSql = $this->generateUpdateSql(array_keys($rows[0]));

        try {
            $stmt = $this->dbh->prepare($insertSql.$updateSql);
            $stmt->execute($this->generateBindings($rows));
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
        $columns = implode('`,`', array_keys($rows[0]));

        $values = [];
        $index = 0;
        foreach ($rows as $row) {
            $value = [];

            foreach ($row as $column) {
                $value[] = ':'.$index++;
            }

            $values[] = '('.implode(', ', $value).')';
        }

        return 'INSERT INTO `'.$tableName.'` (`'.$columns.'`) VALUES ' . implode(', ', $values);
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
