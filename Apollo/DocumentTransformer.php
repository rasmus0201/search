<?php

namespace Apollo;

use PDO;
use Search\Connectors\Traits\CanOpenConnections;
use Search\Indexing\Document;
use Search\Indexing\IndexTransformerInterface;
use Search\Support\Config;

class DocumentTransformer implements IndexTransformerInterface
{
    use CanOpenConnections;

    private $dbh;

    public function __construct(Config $config)
    {
        $this->dbh = $this->createDatabaseHandle($config);
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
