<?php

namespace Search\Import\Apollo;

use PDO;
use Search\Import\Traits\CanInsertMultipleValuesMysql;
use Search\Import\DatabaseImporterInterface;
use Search\Support\Config;
use Search\Support\DB;

class Importer implements DatabaseImporterInterface
{
    use CanInsertMultipleValuesMysql;

    const CHUNK_LIMIT = 500;

    private $dbh;
    private $bookIdToDirectionIdMap = [
        'daen-rød' => 8,
        'enda-rød' => 9,
    ];

    private $wordclassMap = [
        'adj.',
        'adv.',
        'artikel',
        'infinitivmærke',
        'konj.',
        'lydord',
        'pron.',
        'proprium',
        'præfiks',
        'præp.',
        'sb.',
        'suffiks',
        'talord',
        'uden klasse',
        'udråbsord',
        'vb.',
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

            foreach ($this->parse($bookId) as $entry) {
                $entry['direction_id'] = $directionId;
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

    private function parse($book)
    {
        $count = $this->count($book);
        $limit = 500;
        $offset = 0;

        while ($rows = $this->chunk($book, $limit, $offset)) {
            foreach ($rows as $row) {
                $data = $row['data'];

                $idAttributes = $this->extractAttributes($data, 'id-lemma');
                $prioAttributes = $this->extractAttributes($data, 'prioritize-when-lemma');
                $headword = $this->extractInnerContent($data, 'headword');

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
                    'raw_entry_id' => $row['id'],
                    'headword' => $headword,
                    'wordclass' => $wordclass,
                    'lemma_ref' => $lemmaRef,
                ];

                yield $row['id'] => $entry;
            }

            $offset += $limit;
        }
    }

    private function isPhrase($string)
    {
        return strpos('<prioritize-when-lemma') !== false;
    }

    private function extractInnerContent($string, $tag)
    {
        $pattern = '/<'.$tag.'(?:.*?)>(.*?)<\/'.$tag.'>/';
        preg_match($pattern, $string, $rawContent);

        if (!isset($rawContent[1])) {
            return '';
        }

        return $rawContent[1];
    }

    private function extractAttributes($string, $tag)
    {
        $pattern = '/<'.$tag.' (.*?)\/>/';
        preg_match($pattern, $string, $rawIdAttributes);

        if (!isset($rawIdAttributes[1])) {
            return;
        }

        $unformattedAttr = preg_split('/("[^"]*")|\h+/', $rawIdAttributes[1], -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);

        $attributes = [];
        for ($i = 0; $i < count($unformattedAttr); $i += 2) {
            $attributeName = mb_substr($unformattedAttr[$i], 0, -1);
            $attributeValue = mb_substr($unformattedAttr[$i + 1], 1, -1);

            $attributes[$attributeName] = $attributeValue;
        }

        return $attributes;
    }
}
