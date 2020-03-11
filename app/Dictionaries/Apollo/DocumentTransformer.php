<?php

namespace App\Dictionaries\Apollo;

use PDO;
use Search\Indexing\Document;
use Search\Indexing\IndexTransformerInterface;
use Search\Support\DatabaseConfig;
use Search\Support\DB;

class DocumentTransformer implements IndexTransformerInterface
{
    private $dbh;

    public function __construct(DatabaseConfig $config)
    {
        $this->dbh = DB::create($config)->getConnection();
    }

    public function transform(array $row) : Document
    {
        $document = new Document();

        $document->setId($row['id']);
        $document->setDocument($row['document']);

        if ($row['lemma_id']) {
            $stmt = $this->dbh->prepare("
                SELECT i.word
                FROM lemma_inflections i
                WHERE i.lemma_id = :lemma_id
            ");

            $stmt->execute([
                ':lemma_id' => $row['lemma_id']
            ]);

            $inflections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $document->setInflections(array_column($inflections, 'word'));
        }

        return $document;
    }
}
