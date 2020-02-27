<?php

namespace Search\Import\Models;

class Entry
{
    public $headword;
    public $translation;
    public $wordclass;

    public $subjects = [];

    public $headwordAbbreviations = [];
    public $translationAbbreviations = [];

    public $headwordComments = [];
    public $translationComments = [];

    public function parseHeadword()
    {
        $this->headwordAbbreviations = $this->getAnglebrackets($this->headword);
        $this->headwordComments = $this->getSquarebrackets($this->headword);
    }

    public function parseTranslation()
    {
        $this->translationAbbreviations = $this->getAnglebrackets($this->translation);
        $this->translationComments = $this->getSquarebrackets($this->translation);
    }

    public function getParsedSubjects($subjectString)
    {
        return $this->getSquarebrackets($subjectString);
    }

    private function getAnglebrackets($string)
    {
        preg_match_all('/\<.*?\>/', $string, $out);

        return $out[0];
    }

    private function getSquarebrackets($string)
    {
        preg_match_all('/\[.*?\]/', $string, $out);

        return $out[0];
    }
}
