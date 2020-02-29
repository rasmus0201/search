<?php

namespace Search\Repositories;

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

    public function createOrUpdate(Term $term)
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
}
