<?php

namespace Search\Import\Dictcc;

use Search\Import\ImporterInterface;
use Search\Import\Dictcc\Models\Entry;

class Importer implements ImporterInterface
{
    private $lines;
    private $entries;

    public function __construct($filePath)
    {
        $fileContents = file_get_contents($filePath);
        $this->lines = array_filter(preg_split('/[\n\r]+/', $fileContents));
    }

    public function parse($directionId)
    {
        // <angle> ..... abbreviations/acronyms   (don't count as words for sorting, but act as keywords)
        // [square] ..... visible comments   (don't count as words for sorting, don't act as keywords)
        // (round) ....... for optional parts   (count as words for sorting, act as keywords)
        // {curly} ........ word class definitions   (use word class field instead, except for gender tags like {f}, {pl}, ...)

        foreach ($this->lines as $key => $line) {
            if (strpos($line, '#') === 0) {
                continue;
            }

            $data = preg_split('/[\t]+/', $line);

            $headword = $data[0];
            $translation = $data[1];
            $wordclass = '';
            $subject = '';

            if (count($data) > 2) {
                $wordclass = $data[2];
            }

            if (count($data) > 3) {
                $subject = $data[3];
            }

            $entry = new Entry();

            $entry->directionId = $directionId;
            $entry->headword = $headword;
            $entry->translation = $translation;
            $entry->wordclass = $wordclass;

            $entry->subjects = $entry->getParsedSubjects($subject);
            $entry->parseHeadword();
            $entry->parseTranslation();

            $this->entries[] = $entry->jsonSerialize();
        }

        return $this;
    }

    public function toSql()
    {
        $values = '';
        $params = [];
        foreach ($this->entries as $entryData) {
            $values .= '(';

            foreach ($entryData as $column => $value) {
                $values .= '?, ';
                $params[] = $value;
            }

            $values = rtrim($values, ', ') . '), ';
        }

        $values = rtrim($values, ', ');

        return [$values, $params];
    }
}
