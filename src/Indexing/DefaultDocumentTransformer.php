<?php

namespace Search\Indexing;

use Search\Indexing\Document;
use Search\Indexing\IndexTransformerInterface;

class DefaultDocumentTransformer implements IndexTransformerInterface
{
    public function transform(array $row) : Document
    {
        $item = new Document();

        $item->setId($row['id']);
        $item->setDocument($row['document']);

        return $item;
    }
}
