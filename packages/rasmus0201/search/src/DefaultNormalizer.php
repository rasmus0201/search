<?php

namespace Search;

use Search\NormalizerInterface;

class DefaultNormalizer implements NormalizerInterface
{
    public function normalize(string $string) : string
    {
        return mb_strtolower(
            preg_replace('/[^[:alnum:][:space:]\/\'-]/u', '', $string)
        );
    }
}
