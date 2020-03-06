<?php

namespace Search\Repositories;

use PDO;
use Search\Indexing\Term;

class InflectionRepository extends AbstractRepository
{
    public function createTableIfNotExists()
    {
        $this->dbh->exec("CREATE TABLE IF NOT EXISTS inflections (
            `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
            `inflection` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_inflection` (`inflection`),
            KEY `idx_inflection` (`inflection`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $this->dbh->exec("CREATE TABLE IF NOT EXISTS term_has_inflections (
            `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
            `term_id` INT(11) unsigned NOT NULL,
            `inflection_id` INT(11) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_term_inflection` (`term_id`, `inflection_id`),
            KEY `idx_term_id_inflection_id` (`term_id`, `inflection_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    public function createMany(Term $term, array $inflections)
    {
        if (empty($inflections)) {
            return;
        }

        $stmt = $this->dbh->prepare("
            INSERT INTO inflections (`inflection`)
            VALUES (:inflection)
            ON DUPLICATE KEY UPDATE `inflection` = `inflection`
        ");

        foreach ($inflections as $inflection) {
            $stmt->bindValue(':inflection', $inflection);
            $stmt->execute();
        }

        $placeholders = ':' . implode(', :', range(0, count($inflections) - 1));

        $params = [];
        foreach ($inflections as $key => $value) {
            $params[':' . $key] = $value;
        }

        $stmt = $this->dbh->prepare("
            SELECT i.id
            FROM inflections i
            WHERE i.word IN (".$placeholders.")
        ");

        $stmt->execute($params);

        $allInflections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->dbh->prepare("
            INSERT INTO (`term_id`, `inflection_id`)
            VALUES (:term_id, :inflection_id)
        ");

        $stmt->bindValue(':term_id', $term->getId());

        foreach ($allInflections as $inflection) {
            $stmt->bindValue(':inflection_id', $inflection['id']);
            $stmt->execute();
        }
    }

    public function getByTermIds(array $termIds)
    {
        if (empty($termIds)) {
            return [];
        }

        $stmt = $this->dbh->prepare("
            SELECT i.term_id, i.inflection
            FROM inflections i
            WHERE i.term_id IN (".implode(',', $termIds).")
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
