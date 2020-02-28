<?php

namespace Search;

use Search\TermNormalizerInterface;

class DefaultTermNormalizer implements TermNormalizerInterface
{
    public function normalize(string $term) : string
    {
        $term = preg_replace('/\(.*?\)/', '', $term);

        return mb_strtolower(
            preg_replace('/[^[:alnum:][:space:]\/\'-]/u', '', $term)
        );
    }
}
