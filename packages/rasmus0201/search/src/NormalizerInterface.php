<?php

namespace Search;

interface NormalizerInterface
{
    public function normalize(string $string) : string;
}
