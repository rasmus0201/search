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

    public function incrementHits(Term $term)
    {
        $stmt = $this->dbh->prepare("
            UPDATE term_index i
            SET i.`num_docs` = i.`num_docs` + :docs,
                i.`num_hits` = i.`num_hits` + :hits
            WHERE i.`term` = :term
        ");

        $stmt->execute([
            ':docs' => 1,
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

    public function getTermsByKeywords(array $keywords)
    {
        if (empty($keywords)) {
            return [];
        }

        $placeholders = ':' . implode(', :', range(0, count($keywords) - 1));
        $orderPlaceholders = str_replace(':', ':o', $placeholders);

        $params = [];
        foreach ($keywords as $key => $value) {
            $params[':' . $key] = $value;
            $params[':o' . $key] = $value;
        }

        $stmt = $this->dbh->prepare("
            SELECT i.`id`, i.`term`, i.`num_hits`, i.`num_docs` FROM term_index i
            WHERE i.`term` IN (".$placeholders.")
            ORDER BY FIELD(i.`term`, ".$orderPlaceholders.")
        ");

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
