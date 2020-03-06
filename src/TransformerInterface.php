<?php

namespace Search;

use PDO;

interface TransformerInterface
{
    public function setDatabaseHandle(PDO $dbh);
    public function transform(array $input) : array;
}
