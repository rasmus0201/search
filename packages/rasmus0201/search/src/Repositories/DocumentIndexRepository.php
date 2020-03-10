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

    public function getAverageLength()
    {
        $stmt = $this->dbh->prepare("
            SELECT AVG(d.length) as average_document_length
            FROM (
                SELECT di.document_id, COUNT(*) AS length
                FROM document_index di
                GROUP BY di.document_id
            ) d
        ");

        $stmt->execute();

        return (float) $stmt->fetch(PDO::FETCH_ASSOC)['average_document_length'];
    }

    public function getUniqueByTermIds(array $termIds, $limit)
    {
        if (empty($termIds)) {
            return [];
        }

        $stmt = $this->dbh->prepare("
            SELECT di.`document_id`, t.`length`, di.`position`, di.`term_id`, ti.`term`, ti.`num_hits`, ti.`num_docs`
            FROM document_index di

            INNER JOIN (
                SELECT di.`document_id`, COUNT(*) as length
                FROM document_index di

                INNER JOIN term_index ti ON ti.id = di.term_id

                WHERE ti.`id` IN (".implode(',', $termIds).")

                GROUP BY di.`document_id`
            ) t ON t.document_id = di.document_id
            INNER JOIN term_index ti ON ti.id = di.term_id

            ORDER BY
                di.`document_id` ASC,
                di.`position` ASC

            LIMIT :limit
        ");

        $stmt->execute([
            ':limit' => $limit
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStrictUniqueByTermIds(array $termIds, $limit)
    {
        if (empty($termIds)) {
            return [];
        }

        $stmt = $this->dbh->prepare("
            SELECT di.`document_id`, t.`length`, di.`position`, di.`term_id`, ti.`term`, ti.`num_hits`, ti.`num_docs`
            FROM document_index di

            INNER JOIN (
                SELECT di.`document_id`, COUNT(*) as length
                FROM document_index di

                INNER JOIN term_index ti ON ti.id = di.term_id

                WHERE ti.`id` IN (".implode(',', $termIds).")

                GROUP BY di.`document_id`

                HAVING COUNT(DISTINCT ti.`id`) = :length
            ) t ON t.document_id = di.document_id
            INNER JOIN term_index ti ON ti.id = di.term_id

            ORDER BY
                di.`document_id` ASC,
                di.`position` ASC

            LIMIT :limit
        ");

        $stmt->execute([
            ':limit' => $limit,
            ':length' => count($termIds)
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLazyByTerms(array $terms)
    {
        if (empty($terms)) {
            return [];
        }

        $placeholders = ':' . implode(', :', range(0, count($terms) - 1));

        $params = [];
        foreach ($terms as $key => $value) {
            $params[':' . $key] = $value;
        }

        $stmt = $this->dbh->prepare("
            SELECT di.`document_id`, t.`length`, di.`position`, di.`term_id`, ti.`term`, ti.`num_hits`, ti.`num_docs`
            FROM document_index di

            INNER JOIN (
                SELECT di.`document_id`, COUNT(*) as length
                FROM document_index di

                INNER JOIN term_index ti ON ti.id = di.term_id

                WHERE ti.`term` IN (".$placeholders.")

                GROUP BY di.`document_id`
            ) t ON t.document_id = di.document_id
            INNER JOIN term_index ti ON ti.id = di.term_id

            ORDER BY
                di.`document_id` ASC,
                di.`position` ASC
        ");

        $stmt->execute($params);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }
}
