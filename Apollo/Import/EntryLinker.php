<?php

namespace Apollo\Import;

use PDO;
use Search\Support\DatabaseConfig;
use Search\Support\DB;

class EntryLinker
{
    const CHUNK_LIMIT = 10000;

    private $dbh;

    public function __construct(DatabaseConfig $config)
    {
        $this->setConnection($config);
    }

    public function setConnection(DatabaseConfig $config)
    {
        $this->dbh = (new DB($config))->getConnection();
    }

    public function link()
    {
        if (!$count = $this->count()) {
            return;
        }

        $chunks = range(0, floor($count / self::CHUNK_LIMIT));
        foreach ($chunks as $chunk) {
            $this->chunk(self::CHUNK_LIMIT, self::CHUNK_LIMIT * $chunk);
        }
    }

    private function count()
    {
        $stmt = $this->dbh->prepare("
            SELECT COUNT(*) as count FROM entries
            WHERE `lemma_id` IS NULL
            AND `lemma_ref` IS NOT NULL
        ");
        $stmt->execute();

        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    private function chunk($limit, $offset)
    {
        $stmt = $this->dbh->prepare("
            UPDATE `entries` e

            INNER JOIN (
                SELECT e.`id`
                FROM `entries` e
                ORDER BY e.`id`
                LIMIT :offset, :limit
            ) as tmp USING (`id`)

            INNER JOIN lemmas l
                ON l.`lemma_ref` = e.`lemma_ref`

            SET e.`lemma_id` = l.`id`

            WHERE e.`lemma_id` IS NULL
            AND e.`lemma_ref` IS NOT NULL
        ");

        $stmt->execute([
            ':offset' => $offset,
            ':limit' => $limit,
        ]);

        $stmt->execute();
    }
}
