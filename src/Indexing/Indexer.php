<?php

namespace Search\Indexing;

use Search\Indexing\IndexItem;
use Search\Indexing\IndexTransformerInterface;
use Search\TermNormalizerInterface;

class Indexer
{
    private $transformer;
    private $normalizer;

    public function __construct(
        IndexTransformerInterface $transformer,
        TermNormalizerInterface $normalizer
    ) {
        $this->transformer = $transformer;
        $this->normalizer = $normalizer;
    }

    /**
     * Index entries by doc-at-a-time
     *
     * @param mixed[] $documents
     *
     * @yield IndexItem
     */
    function index(array $documents)
    {
        foreach ($documents as $document) {
            $item = $this->transformer->transform($document);

            // Normalize input to only contain valid data
            // alphanumericals, including: -,.'"
            $item->setTerm(
                $this->normalizer->normalize($item->getTerm())
            );

            $terms = preg_split('/[[:space:]]/', $item->getTerm());

            foreach ($terms as $position => $term) {
                $indexItem = new IndexItem();
                $indexItem->setPosition($position);
                $indexItem->setTerm($term);
                $indexItem->setDocument($item->getDocument());

                yield $item->getId() => $indexItem;
            }
        }
    }
}
