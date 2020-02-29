<?php

namespace Search\Repositories;

class InfoRepository extends AbstractRepository
{
    public function createTableIfNotExists()
    {
        $this->dbh->exec("CREATE TABLE IF NOT EXISTS info (
            `key` VARCHAR(255) NOT NULL,
            `value` INT(11),
            PRIMARY KEY (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $this->dbh->exec("
            INSERT INTO info (`key`, `value`)
            VALUES ('total_documents', 0)
            ON DUPLICATE KEY UPDATE `key` = `key`
        ");
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
