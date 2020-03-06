<?php

namespace Search\Repositories;

use PDO;

class InflectionRepository extends AbstractRepository
{
    public function getByTermIds(array $termIds)
    {
        if (empty($termIds)) {
            return [];
        }

        $stmt = $this->dbh->prepare("
            SELECT DISTINCT ti.id, tmp.word

            FROM (
                SELECT DISTINCT ti.id, i.word
                FROM term_index ti

                INNER JOIN document_index di ON ti.id = di.term_id
                INNER JOIN entries e ON di.document_id = e.id
                INNER JOIN inflections i ON e.lemma_id = i.lemma_id

                WHERE BINARY ti.id IN (".implode(',', $termIds).")
            ) tmp

            INNER JOIN term_index ti ON BINARY tmp.word = ti.term
        ");

        $stmt->execute();

        dd($stmt->fetchAll(PDO::FETCH_ASSOC));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
