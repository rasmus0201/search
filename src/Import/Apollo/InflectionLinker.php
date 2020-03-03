<?php

namespace Search\Import\Apollo;

use PDO;
use Search\Support\Config;
use Search\Support\DB;

class InflectionLinker
{
    const CHUNK_LIMIT = 10000;

    private $dbh;

    public function __construct(Config $config)
    {
        $this->setConnection($config);
    }

    public function setConnection(Config $config)
    {
        $this->dbh = (new DB($config))->getConnection();
    }

    public function link()
    {
        if (!$this->count()) {
            return;
        }

        $offset = 0;
        while ($this->count() > 0) {
            $this->chunk(self::CHUNK_LIMIT, $offset);

            $offset += self::CHUNK_LIMIT;
        }
    }

    private function count()
    {
        $stmt = $this->dbh->prepare("
            SELECT COUNT(*) as count FROM inflections
            WHERE `lemma_id` IS NULL
        ");
        $stmt->execute();

        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    private function chunk($limit, $offset)
    {
        $stmt = $this->dbh->prepare("
            UPDATE `inflections` i

            INNER JOIN (
                SELECT i.`id`
                FROM `inflections` i
                ORDER BY `id`
                LIMIT :offset, :limit
            ) as tmp USING (`id`)

            INNER JOIN lemmas l
                ON l.`raw_lemma_id` = i.`raw_lemma_id`

            SET i.`lemma_id` = l.`id`

            WHERE i.`lemma_id` IS NULL
        ");

        $stmt->execute([
            ':offset' => $offset,
            ':limit' => $limit,
        ]);

        $stmt->execute();
    }
}
