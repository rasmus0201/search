<?php

namespace Search\Import\Apollo;

use Search\Import\AbstractImporter;
use Search\Import\ImporterInterface;

class Importer extends AbstractImporter implements ImporterInterface
{
    protected function parse()
    {
        // while (($line = fgets($this->handle)) !== false) {
        //     // How to do this?
        //
        //     yield $entry->jsonSerialize();
        // }
    }
}
