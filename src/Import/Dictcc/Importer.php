<?php

namespace Search\Import\Dictcc;

use Search\Import\AbstractImporter;
use Search\Import\ImporterInterface;
use Search\Import\Dictcc\Models\Entry;

class Importer extends AbstractImporter implements ImporterInterface
{
    protected function parse()
    {
        // <angle> ..... abbreviations/acronyms   (don't count as words for sorting, but act as keywords)
        // [square] ..... visible comments   (don't count as words for sorting, don't act as keywords)
        // (round) ....... for optional parts   (count as words for sorting, act as keywords)
        // {curly} ........ word class definitions   (use word class field instead, except for gender tags like {f}, {pl}, ...)

        while (($line = fgets($this->handle)) !== false) {
            if (strpos($line, '#') === 0) {
                continue;
            }

            if (trim($line) === '') {
                continue;
            }

            $data = preg_split('/[\t]+/', $line);

            $headword = trim($data[0]);
            $translation = trim($data[1]);
            $wordclass = '';
            $subject = '';

            if (count($data) > 2) {
                $wordclass = trim($data[2]);
            }

            if (count($data) > 3) {
                $subject = trim($data[3]);
            }

            $entry = new Entry();

            $entry->directionId = $this->directionId;
            $entry->headword = $headword;
            $entry->translation = $translation;
            $entry->wordclass = $wordclass;

            $entry->subjects = $entry->getParsedSubjects($subject);
            $entry->parseHeadword();
            $entry->parseTranslation();

            yield $entry->jsonSerialize();
        }
    }
}
