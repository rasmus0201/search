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

    public function getUniqueIdsByTermIds(array $termIds, $limit)
    {
        if (empty($termIds)) {
            return [];
        }

        $stmt = $this->dbh->prepare("
            SELECT DISTINCT i.`document_id` FROM document_index i
            WHERE i.`term_id` IN (" . implode(',', $termIds) . ")
            GROUP BY i.`document_id`
            LIMIT :limit
        ");

        $stmt->execute([
            ':limit' => $limit,
        ]);

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'document_id');
    }

    public function getByIds(array $documentIds)
    {
        if (empty($documentIds)) {
            return [];
        }

        $stmt = $this->dbh->prepare("
            SELECT i.`document_id`, i.`term_id`, i.`position` FROM document_index i
            WHERE i.`document_id` IN (" . implode(',', $documentIds) . ")
            ORDER BY i.`document_id`, i.`position`
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);;
    }
}
