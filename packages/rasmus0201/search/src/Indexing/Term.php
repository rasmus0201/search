<?php

namespace Search\Indexing;

class Term
{
    private $id;
    private $documentId;
    private $documentFrequency = 0;
    private $position;
    private $term;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setDocumentId($documentId)
    {
        $this->documentId = $documentId;
    }

    public function getDocumentId()
    {
        return $this->documentId;
    }

    public function setDocumentFrequency($documentFrequency)
    {
        $this->documentFrequency = $documentFrequency;
    }

    public function getDocumentFrequency()
    {
        return $this->documentFrequency;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setTerm($term)
    {
        $this->term = $term;
    }

    public function getTerm()
    {
        return $this->term;
    }
}
