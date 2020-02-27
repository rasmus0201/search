<?php

namespace Search\Import;

use Search\Import\ImporterInterface;
use Search\Import\Models\Entry;

class DictccImporter implements ImporterInterface
{
    private $lines;
    private $entries;

    public function __construct($filePath)
    {
        $fileContents = file_get_contents($filePath);
        $this->lines = array_filter(preg_split('/[\n\r]+/', $fileContents));
    }

    public function parse()
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

            // When 4 this means there is a wordclass
            // and subject
            if (count($data) === 4) {
                $wordclass = $data[2];
                $subject = $data[3];
            }

            $entry = new Entry();

            $entry->headword = $headword;
            $entry->translation = $translation;
            $entry->wordclass = $wordclass;
            $entry->subjects = $entry->getParsedSubjects($subject);

            $entry->parseHeadword();
            $entry->parseTranslation();

            dump();

            $this->entries[] = $entry;
        }

        return $this;
    }

    public function entries()
    {
        dump($this->entries);
    }

    public function lemmas()
    {

    }
}
