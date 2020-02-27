<?php

namespace Search\Import;

interface ImporterInterface
{
    public function __construct($filePath);

    public function entries();

    public function lemmas();
}
