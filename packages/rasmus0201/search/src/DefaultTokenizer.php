<?php

namespace Search;

use Search\TokenizerInterface;

class DefaultTokenizer implements TokenizerInterface
{
    public function tokenize(string $string) : array
    {
        return preg_split("/[^\p{L}\p{N}]+/u", mb_strtolower($string), -1, PREG_SPLIT_NO_EMPTY);
    }
}
