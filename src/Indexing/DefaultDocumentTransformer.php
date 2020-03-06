<?php

namespace Search\Indexing;

use Search\Indexing\Document;
use Search\Indexing\IndexTransformerInterface;

class DefaultDocumentTransformer implements IndexTransformerInterface
{
    public function transform(array $row) : Document
    {
        $document = new Document();

        $document->setId($row['id']);
        $document->setDocument($row['document']);

        return $document;
    }
}
