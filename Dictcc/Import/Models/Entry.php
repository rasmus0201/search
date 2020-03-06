<?php

namespace Dictcc\Import\Models;

class Entry implements \JsonSerializable
{
    public $id;
    public $directionId;
    public $headword;
    public $translation;
    public $wordclass;

    public $subjects = [];

    public $headwordAbbreviations = [];
    public $translationAbbreviations = [];

    public $headwordComments = [];
    public $translationComments = [];

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'direction_id' => $this->directionId,
            'headword' => $this->headword,
            'translation' => $this->translation,
            'wordclass' => $this->wordclass,
        ];
    }

    public function parseHeadword()
    {
        $this->headwordAbbreviations = $this->getAnglebrackets($this->headword);
        $this->headwordComments = $this->getSquarebrackets($this->headword);
        $wordTypes = $this->getCurlybrackets($this->headword);

        $this->headword = str_replace($this->headwordAbbreviations, '', $this->headword);
        $this->headword = str_replace($this->headwordComments, '', $this->headword);
        $this->headword = str_replace($wordTypes, '', $this->headword);
        $this->headword = trim(preg_replace('/[[:blank:]]+/', ' ', $this->headword));
    }

    public function parseTranslation()
    {
        $this->translationAbbreviations = $this->getAnglebrackets($this->translation);
        $this->translationComments = $this->getSquarebrackets($this->translation);
        $wordTypes = $this->getCurlybrackets($this->translation);

        $this->translation = str_replace($this->translationAbbreviations, '', $this->translation);
        $this->translation = str_replace($this->translationComments, '', $this->translation);
        $this->translation = str_replace($wordTypes, '', $this->translation);
        $this->translation = trim(preg_replace('/[[:blank:]]+/', ' ', $this->translation));
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

    private function getCurlybrackets($string)
    {
        preg_match_all('/\{.*?\}/', $string, $out);

        return $out[0];
    }
}
