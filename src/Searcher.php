<?php

namespace Search;

class Searcher
{
    private $dictionary;
    private $directions;

    /**
     * Constructor
     *
     * @param int $dictionaryId
     * @param string[] $directions
     */
    function __construct($dictionaryId, array $directions)
    {
        $this->dictionary = $dictionaryId;
        $this->directions = $directions;
    }

    /**
     * Get results from a search input.
     *
     * @param string $searchInput
     *
     * @return int[]
     */
    function results($searchInput)
    {
        // Normalize input to only contain alphanumericals, including: -,.'"
        // Check if searchInput has exact match in search index
        // Check if searchInput does not have space
        //      -> Assumed not a lemma in the dictionary
        //      -> Do some fuzzy to check for closest matches.
        // Check if searchInput have space
            // split search input by spaces.
    }
}
