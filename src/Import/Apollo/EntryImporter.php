<?php

namespace Search\Import\Apollo;

use PDO;
use Search\Import\Apollo\XmlHelper;
use Search\Import\Traits\CanInsertMultipleValuesMysql;
use Search\Import\DatabaseImporterInterface;
use Search\Support\Config;
use Search\Support\DB;

class EntryImporter implements DatabaseImporterInterface
{
    use CanInsertMultipleValuesMysql;

    const CHUNK_LIMIT = 1000;

    private $dbh;
    private $bookIdToDirectionIdMap = [
        'daen-rød' => 8,
        'enda-rød' => 9,
    ];

    public function __construct(Config $config)
    {
        $this->setConnection($config);
    }

    public function setConnection(Config $config)
    {
        $this->dbh = (new DB($config))->getConnection();
    }

    public function import($toTableName)
    {
        foreach ($this->bookIdToDirectionIdMap as $bookId => $directionId) {
            $rows = [];

            foreach ($this->parse($bookId, $directionId) as $entry) {
                $rows[] = $entry;

                if (count($rows) === self::CHUNK_LIMIT) {
                    $this->performInsert($toTableName, $rows);

                    $rows = [];
                }
            }

            // If the last run is less than chunk, there will be remaining rows.
            if (count($rows) > 0) {
                $this->performInsert($toTableName, $rows);
            }
        }
    }

    private function count($book)
    {
        $stmt = $this->dbh->prepare("
            SELECT COUNT(*) as count FROM raw_entries
            WHERE book = :book
        ");

        $stmt->execute([
            ':book' => $book,
        ]);

        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    private function chunk($book, $limit, $offset)
    {
        $stmt = $this->dbh->prepare("
            SELECT e.id, e.data FROM raw_entries e
            WHERE e.book = :book
            LIMIT :offset, :limit
        ");

        $stmt->execute([
            ':book' => $book,
            ':offset' => $offset,
            ':limit' => $limit,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function parse($book, $directionId)
    {
        $count = $this->count($book);
        $offset = 0;

        while ($rows = $this->chunk($book, self::CHUNK_LIMIT, $offset)) {
            foreach ($rows as $row) {
                $data = $row['data'];

                $idAttributes = XmlHelper::extractAttributes($data, 'id-lemma');
                $prioAttributes = XmlHelper::extractAttributes($data, 'prioritize-when-lemma');
                $headword = XmlHelper::extractInnerContent($data, 'headword');

                $lemmaRef = null;
                $wordclass = null;

                if ($idAttributes) {
                    $lemmaRef = $idAttributes['lemmaid-ref'] ?? null;
                    $wordclass = $idAttributes['lemma-pos'] ?? null;
                }

                if ($prioAttributes) {
                    $lemmaRef = $prioAttributes['lemmaid-ref'] ?? null;
                    $wordclass = $idAttributes['lemma-pos'] ?? null;
                }

                $entry = [
                    'direction_id' => $directionId,
                    'raw_entry_id' => $row['id'],
                    'lemma_id' => null,
                    'headword' => $headword,
                ];

                yield $row['id'] => $entry;
            }

            $offset += self::CHUNK_LIMIT;
        }
    }

    private function isPhrase($string)
    {
        return strpos('<prioritize-when-lemma') !== false;
    }
}
