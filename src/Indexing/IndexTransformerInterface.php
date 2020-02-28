<?php

namespace Search\Indexing;

use Search\Indexing\IndexItem;

interface IndexTransformerInterface
{
    public function transform(array $document) : IndexItem;
    public function reverse(IndexItem $indexItem) : array;
}
