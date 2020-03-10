<?php

namespace Search\Searching;

use Search\NormalizerInterface;
use Search\TokenizerInterface;
use Search\Support\DatabaseConfig;
use Search\Support\SearchConfig;

interface SearchInterface
{
    public function __construct(
        DatabaseConfig $config,
        SearchConfig $settings,
        NormalizerInterface $normalizer,
        TokenizerInterface $tokenizer
    );

    public function search($searchPhrase, $numOfResults = 25) : SearchResult;
}
