<?php

namespace Search\Repositories;

use PDO;
use Search\Indexing\Term;

class TermIndexRepository extends AbstractRepository
{
    public function createTableIfNotExists()
    {
        $this->dbh->exec("CREATE TABLE IF NOT EXISTS term_index (
            `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
            `term` VARCHAR(255) NOT NULL,
            `num_hits` INT(11) NOT NULL,
            `num_docs` INT(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_term` (`term`),
            KEY `idx_term` (`term`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    public function createOrUpdate(Term $term)
    {
        $stmt = $this->dbh->prepare("
            INSERT INTO term_index (`term`, `num_hits`, `num_docs`)
            VALUES (:term, :freq1, 1)
            ON DUPLICATE KEY UPDATE
                `num_hits` = (VALUES(`num_hits`) + :freq2),
                `num_docs` = (VALUES(`num_docs`) + 1)
        ");

        return $stmt->execute([
            ':term' => $term->getTerm(),
            ':freq1' => $term->getDocumentFrequency(),
            ':freq2' => $term->getDocumentFrequency(),
        ]);
    }

    public function getTermId(Term $term)
    {
        $stmt = $this->dbh->prepare("
            SELECT `id` FROM term_index
            WHERE `term` = :term
        ");

        $stmt->execute([
            ':term' => $term->getTerm(),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !isset($row['id'])) {
            throw new Exception("Id not found for term '{$term->getTerm()}'");
        }

        return $row['id'];
    }
}
