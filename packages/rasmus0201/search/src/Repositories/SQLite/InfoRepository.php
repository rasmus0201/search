<?php

namespace Search\Repositories\SQLite;

use Exception;
use PDO;
use Search\Repositories\AbstractRepository;
use Search\Repositories\InfoRepositoryInterface;

class InfoRepository extends AbstractRepository implements InfoRepositoryInterface
{
    public function createTableIfNotExists()
    {
        $this->dbh->exec("CREATE TABLE IF NOT EXISTS info (
            `key` TEXT NOT NULL,
            `value` FLOAT(11, 4),
            PRIMARY KEY (`key`)
        )");

        $this->dbh->exec("
            INSERT INTO info (`key`, `value`)
            VALUES ('total_documents', 0)
            ON CONFLICT(`key`) DO UPDATE SET `key` = `key`
        ");

        $this->dbh->exec("
            INSERT INTO info (`key`, `value`)
            VALUES ('average_document_length', 0)
            ON CONFLICT(`key`) DO UPDATE SET `key` = `key`
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
        $stmt = $this->dbh->prepare("UPDATE info SET `value` = :val WHERE `key` = :key");
        $stmt->execute([
            ':key' => $key,
            ':val' => $value,
        ]);
    }
}
