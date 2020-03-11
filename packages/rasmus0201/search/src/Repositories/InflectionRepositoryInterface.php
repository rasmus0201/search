<?php

namespace Search\Repositories;

use PDO;
use Search\Indexing\Term;

interface InflectionRepositoryInterface
{
    public function setDatabaseHandle(PDO $databaseHandle);

    public function createTableIfNotExists();

    public function createMany(Term $term, array $inflections);

    public function getByKeywords(array $keywords);
}
