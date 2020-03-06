<?php

namespace Search\Indexing;

class Document
{
    private $id;
    private $document;
    private $inflections = [];

    public function setDocument($document)
    {
        $this->document = $document;
    }

    public function getDocument()
    {
        return $this->document;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setInflections(array $inflections)
    {
        $this->inflections = $inflections;
    }

    public function getInflections()
    {
        return $this->inflections;
    }
}
