<?php

namespace Search\Repositories;

use PDO;
use Search\Indexing\Term;

interface DocumentIndexRepositoryInterface
{
    public function setDatabaseHandle(PDO $databaseHandle);

    public function createTableIfNotExists();

    public function create(Term $term);

    public function getAverageLength();

    public function getUniqueByTermIds(array $termIds, $limit);

    public function getStrictUniqueByTermIds(array $termIds, $limit);

    public function getLazyByTerms(array $terms);
}
