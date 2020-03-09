<?php

namespace App\Dictionaries\Apollo\Import;

use PDO;
use Search\Import\XmlHelper;
use Search\Import\Traits\CanInsertMultipleValuesMysql;
use Search\Import\DatabaseImporterInterface;
use Search\Support\DatabaseConfig;
use Search\Support\DB;

class LemmaImporter implements DatabaseImporterInterface
{
    use CanInsertMultipleValuesMysql;

    const CHUNK_LIMIT = 1000;

    private $dbh;
    private $inflectionTable;
    private $languages = [
        'dan',
        'eng',
    ];

    public function __construct(DatabaseConfig $config)
    {
        $this->setConnection($config);
    }

    public function setConnection(DatabaseConfig $config)
    {
        $this->dbh = (new DB($config))->getConnection();
    }

    public function setInflectionTable($table)
    {
        $this->inflectionTable = $table;
    }

    public function import($toTableName)
    {
        foreach ($this->languages as $language) {
            $rows = [];

            foreach ($this->parse($language) as $lemma) {
                $rows[] = $lemma;

                if (count($rows) === self::CHUNK_LIMIT) {
                    list($rows, $inflections) = $this->seperateInflections($rows);
                    $this->performInsert($toTableName, $rows);

                    if ($this->inflectionTable) {
                        $this->performInsert($this->inflectionTable, $inflections);
                    }

                    $rows = [];
                }
            }

            // If the last run is less than chunk, there will be remaining rows.
            if (count($rows) > 0) {
                list($rows, $inflections) = $this->seperateInflections($rows);
                $this->performInsert($toTableName, $rows);

                if ($this->inflectionTable) {
                    $this->performInsert($this->inflectionTable, $inflections);
                }
            }
        }
    }

    private function seperateInflections(array $rows)
    {
        $inflectionGroups = array_values(array_filter(array_column($rows, 'inflections')));

        $inflectionsFlattened = [];
        if (count($inflectionGroups )) {
            $inflectionsFlattened = array_merge(...$inflectionGroups);
        }

        array_walk($rows, function (&$v) {
            if (isset($v['inflections'])) {
                unset($v['inflections']);
            }
        });

        return [$rows, $inflectionsFlattened];
    }

    private function count($language)
    {
        $stmt = $this->dbh->prepare("
            SELECT COUNT(*) as count FROM raw_lemmas
            WHERE lang = :language
        ");

        $stmt->execute([
            ':language' => $language,
        ]);

        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    private function chunk($language, $limit, $offset)
    {
        $stmt = $this->dbh->prepare("
            SELECT l.id, l.lemma_id as lemma_ref, l.data FROM raw_lemmas l
            WHERE l.lang = :language
            LIMIT :offset, :limit
        ");

        $stmt->execute([
            ':language' => $language,
            ':offset' => $offset,
            ':limit' => $limit,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function parse($language)
    {
        $count = $this->count($language);
        $offset = 0;

        while ($rows = $this->chunk($language, self::CHUNK_LIMIT, $offset)) {
            foreach ($rows as $row) {
                $data = $row['data'];

                $word = XmlHelper::extractInnerContent($data, 'ortography');
                $wordclassAttributes = XmlHelper::extractAttributes($data, 'pos');

                $wordclass = null;
                if (isset($wordclassAttributes['name-eng'])) {
                    $wordclass = $wordclassAttributes['name-eng'];
                }

                $lemma = [
                    'raw_lemma_id' => $row['id'],
                    'lemma_ref' => $row['lemma_ref'],
                    'word' => $word,
                    'wordclass' => $wordclass,
                    'inflections' => [],
                ];

                if (XmlHelper::hasTags($data, ['inflection', 'searchable-paradigm'])) {
                    $xml = XmlHelper::extractInnerContent($data, 'searchable-paradigm');
                    $inflectionsXml = XmlHelper::extractInnerTagsByTag($xml, 'inflected-form');

                    foreach ($inflectionsXml as $inflectionXml) {
                        if (!XmlHelper::hasTags($inflectionXml, ['inflection-category', 'full-form'])) {
                            continue;
                        }

                        $word = XmlHelper::extractInnerContent($inflectionXml, 'full-form');

                        if (empty($word)) {
                            continue;
                        }

                        $form = null;
                        $attributes = XmlHelper::extractAttributes($inflectionXml, 'inflection-category');
                        if (isset($attributes['name-eng'])) {
                            $form = $attributes['name-eng'];
                        }

                        $lemma['inflections'][] = [
                            'raw_lemma_id' => $row['id'],
                            'lemma_id' => null,
                            'word' => $word,
                            'form' => $form,
                        ];
                    }
                }

                yield $row['id'] => $lemma;
            }

            $offset += self::CHUNK_LIMIT;
        }
    }
}
