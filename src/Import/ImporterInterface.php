<?php

namespace Search\Import;

interface ImporterInterface
{
    public function __construct($filePath);

    public function parse($directionId);

    public function toSql();
}
