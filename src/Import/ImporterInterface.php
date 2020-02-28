<?php

namespace Search\Import;

interface ImporterInterface
{
    public function __construct($filePath, $directionId);

    public function import($tableName);
}
