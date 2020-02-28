<?php

namespace Search;

interface TermNormalizerInterface
{
    public function normalize(string $term) : string;
}
