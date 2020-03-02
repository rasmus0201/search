<?php

namespace Search\Import;

interface FileImporterInterface
{
    public function __construct($filePath, $directionId);

    public function import($toTableName);
}
