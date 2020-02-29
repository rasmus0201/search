<?php

namespace Search;

interface TokenizerInterface
{
    public function tokenize(string $string) : array;
}
