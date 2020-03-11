<?php

namespace Search\Repositories\SQLite;

use PDO;
use Search\Indexing\Term;
use Search\Repositories\AbstractRepository;
use Search\Repositories\InflectionRepositoryInterface;

class InflectionRepository extends AbstractRepository implements InflectionRepositoryInterface
{
    public function createTableIfNotExists()
    {
        $this->dbh->exec("CREATE TABLE IF NOT EXISTS inflections (
            `id` INTEGER NOT NULL ,
            `inflection` TEXT NOT NULL,
            PRIMARY KEY (`id`)
        )");

        $this->dbh->exec("CREATE UNIQUE INDEX `_unique_inflection` ON inflections (`inflection`)");
        $this->dbh->exec("CREATE INDEX `_idx_inflection` ON inflections (`inflection`)");

        $this->dbh->exec("CREATE TABLE IF NOT EXISTS term_has_inflections (
            `id` INTEGER NOT NULL ,
            `term_id` INTEGER NOT NULL,
            `inflection_id` INTEGER NOT NULL,
            PRIMARY KEY (`id`)
        )");

        $this->dbh->exec("CREATE UNIQUE INDEX `_unique_term_inflection` ON term_has_inflections (`term_id`) COLLATE BINARY");
        $this->dbh->exec("CREATE INDEX `_idx_term_id_inflection_id` ON term_has_inflections (`term_id`) COLLATE BINARY");
        $this->dbh->exec("CREATE INDEX `_idx_inflection_id_term_id` ON term_has_inflections (`inflection_id`) COLLATE BINARY");
    }

    public function createMany(Term $term, array $inflections)
    {
        if (empty($inflections)) {
            return;
        }

        $stmt = $this->dbh->prepare("
            INSERT INTO inflections (`inflection`)
            VALUES (:inflection)
            ON CONFLICT(`inflection`) DO UPDATE SET `inflection` = `inflection`
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
            WHERE i.inflection IN (".$placeholders.")
        ");

        $stmt->execute($params);

        $allInflections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->dbh->prepare("
            INSERT INTO term_has_inflections (`term_id`, `inflection_id`)
            VALUES (:term_id, :inflection_id)
        ");

        $stmt->bindValue(':term_id', $term->getId());

        foreach ($allInflections as $inflection) {
            $stmt->bindValue(':inflection_id', $inflection['id']);
            $stmt->execute();
        }
    }

    public function getByKeywords(array $keywords)
    {
        if (empty($keywords)) {
            return [];
        }

        $placeholders = ':' . implode(', :', range(0, count($keywords) - 1));

        $params = [];
        foreach ($keywords as $key => $value) {
            $params[':' . $key] = $value;
        }

        $stmt = $this->dbh->prepare("
            SELECT thi.term_id, i.inflection
            FROM term_has_inflections thi

            INNER JOIN (
                SELECT DISTINCT thi.term_id
                FROM term_has_inflections thi
                INNER JOIN inflections i ON i.id = thi.inflection_id
                WHERE i.inflection IN (".$placeholders.")
            ) t ON t.term_id = thi.term_id
            INNER JOIN inflections i ON i.id = thi.inflection_id
        ");

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
