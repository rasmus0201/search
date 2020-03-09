<?php

namespace Search\Indexing;

use Search\Indexing\Document;

interface IndexTransformerInterface
{
    public function transform(array $row) : Document;
}
