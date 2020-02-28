<?php

namespace Search\Indexing;

use Search\Indexing\IndexItem;
use Search\Indexing\IndexTransformerInterface;

class DictccTransformer implements IndexTransformerInterface
{
    public function transform(array $document) : IndexItem
    {
        $item = new IndexItem();

        $item->setDocument($document);
        $item->setTerm($document['headword']);
        $item->setId($document['id']);

        return $item;
    }

    public function reverse(IndexItem $indexItem) : array
    {
        return array_merge($indexItem->getDocument(),
            [
                'id'       => $indexItem->getId(),
                'headword' => $indexItem->getWord()
            ]
        );
    }
}
