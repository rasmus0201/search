<?php

namespace Search\Repositories;

use Exception;
use PDO;

class InfoRepository extends AbstractRepository
{
    public function createTableIfNotExists()
    {
        $this->dbh->exec("CREATE TABLE IF NOT EXISTS info (
            `key` VARCHAR(255) NOT NULL,
            `value` FLOAT(11, 4),
            PRIMARY KEY (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $this->dbh->exec("
            INSERT INTO info (`key`, `value`)
            VALUES ('total_documents', 0)
            ON DUPLICATE KEY UPDATE `key` = `key`
        ");

        $this->dbh->exec("
            INSERT INTO info (`key`, `value`)
            VALUES ('average_document_length', 0)
            ON DUPLICATE KEY UPDATE `key` = `key`
        ");
    }

    public function getValueByKey($key)
    {
        $stmt = $this->dbh->prepare("
            SELECT `value` FROM info
            WHERE `key` = :key
            LIMIT 1
        ");

        $stmt->execute([
            ':key' => $key,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !isset($row['value'])) {
            throw new Exception("Value not found for key '{$key}'");
        }

        return $row['value'];
    }

    public function updateByKey($key, $value)
    {
        $stmt = $this->dbh->prepare('UPDATE info i SET i.`value` = :val WHERE i.`key` = :key');
        $stmt->execute([
            ':key' => $key,
            ':val' => $value,
        ]);
    }
}
