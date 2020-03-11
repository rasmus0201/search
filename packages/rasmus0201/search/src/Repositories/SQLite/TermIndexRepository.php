<?php

namespace Search\Repositories\SQLite;

use Exception;
use PDO;
use Search\Indexing\Term;
use Search\Repositories\AbstractRepository;
use Search\Repositories\TermIndexRepositoryInterface;

class TermIndexRepository extends AbstractRepository implements TermIndexRepositoryInterface
{
    public function createTableIfNotExists()
    {
        $this->dbh->exec("CREATE TABLE IF NOT EXISTS term_index (
            `id` INTEGER  NOT NULL ,
            `term` TEXT NOT NULL,
            `num_hits` INTEGER NOT NULL,
            `num_docs` INTEGER NOT NULL,
            PRIMARY KEY (`id`)
        )");

        $this->dbh->exec("CREATE UNIQUE INDEX `_unique_term` ON term_index (`term`)");
        $this->dbh->exec("CREATE INDEX `_idx_term` ON term_index (`term`)");
    }

    public function create(Term $term)
    {
        $stmt = $this->dbh->prepare("
            INSERT INTO term_index (`term`, `num_hits`, `num_docs`)
            VALUES (:term, :freq1, 1)
        ");

        $stmt->execute([
            ':term' => $term->getTerm(),
            ':freq1' => $term->getDocumentFrequency(),
        ]);
    }

    public function incrementHits(Term $term, $increment = 1)
    {
        $stmt = $this->dbh->prepare("
            UPDATE term_index
            SET `num_docs` = `num_docs` + :docs,
                `num_hits` = `num_hits` + :hits
            WHERE `term` = :term
        ");

        $stmt->execute([
            ':docs' => $increment,
            ':hits' => $term->getDocumentFrequency(),
            ':term' => $term->getTerm(),
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

    public function getByKeywords(array $keywords)
    {
        if (empty($keywords)) {
            return [];
        }

        $placeholders = ':' . implode(', :', range(0, count($keywords) - 1));

        $params = [];
        $order = [];
        foreach ($keywords as $key => $value) {
            $params[':' . $key] = $value;
            $params[':order_term_'.$key] = $value;
            $order[] = 'WHEN '.':order_term_'.$key. ' THEN '.$key;
        }

        $stmt = $this->dbh->prepare("
            SELECT i.`id`, i.`term`, i.`num_hits`, i.`num_docs`
            FROM term_index i
            WHERE i.`term` IN (".$placeholders.")
            ORDER BY
                CASE i.`term`
                    ".implode("\n", $order)."
                END
        ");

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLowFrequencyTerms(array $keywords, $totalDocuments, $cutoffFrequency = 0.01)
    {
        $terms = $this->getByKeywords($keywords);
        $termsPopularity = array_column($terms, 'num_docs', 'term');

        $lowFreqTerms = [];
        foreach ($keywords as $keyword) {
            if (!isset($termsPopularity[$keyword])) {
                $lowFreqTerms[] = $keyword;

                continue;
            }

            if (($termsPopularity[$keyword] / $totalDocuments) < $cutoffFrequency) {
                $lowFreqTerms[] = $keyword;
            }
        }

        return [$lowFreqTerms, $terms];
    }
}
