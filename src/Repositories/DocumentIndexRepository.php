<?php

namespace Search\Repositories;

use PDO;
use Search\Indexing\Term;

class DocumentIndexRepository extends AbstractRepository
{
    public function createTableIfNotExists()
    {
        $this->dbh->exec("CREATE TABLE IF NOT EXISTS document_index (
            `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
            `document_id` INT(11) unsigned NOT NULL,
            `term_id` INT(11) unsigned NOT NULL,
            `position` INT(11) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_document_term_position` (`document_id`, `term_id`, `position`),
            KEY `idx_term_position` (`term_id`, `position`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    public function create(Term $term)
    {
        $stmt = $this->dbh->prepare("
            INSERT INTO document_index (`document_id`, `term_id`, `position`)
            VALUES (:document_id, :term_id, :position)
        ");

        return $stmt->execute([
            ':document_id' => $term->getDocumentId(),
            ':term_id' => $term->getId(),
            ':position' => $term->getPosition(),
        ]);
    }

    public function getDocumentHitsByTermId($termId, $default = 0)
    {
        $stmt = $this->dbh->prepare("
            SELECT `num_docs` FROM document_index
            WHERE `term_id` = :termId
        ");

        $stmt->execute([
            ':termId' => $termId,
        ]);

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['num_docs'];
        }

        return $default;
    }

    public function getTermHitsInDocumentByTermId($termId, $limit)
    {
        $stmt = $this->dbh->prepare("
            SELECT i.`document_id`, COUNT(*) as hit_count FROM document_index i
            WHERE term_id = :termId
            GROUP BY i.`document_id`
            LIMIT :limit
        ");

        $stmt->execute([
            ':termId' => $termId,
            ':limit' => $limit,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
