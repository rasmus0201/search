<?php

namespace Search;

use Search\TokenizerInterface;

class DefaultTokenizer implements TokenizerInterface
{
    public function tokenize(string $string) : array
    {
        return preg_split('/[[:space:]]/', $string);
    }
}
