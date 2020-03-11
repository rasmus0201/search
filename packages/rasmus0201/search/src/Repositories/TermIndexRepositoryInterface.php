<?php

namespace Search\Repositories;

use PDO;
use Search\Indexing\Term;

interface TermIndexRepositoryInterface
{
    public function setDatabaseHandle(PDO $databaseHandle);

    public function createTableIfNotExists();

    public function create(Term $term);

    public function incrementHits(Term $term, $increment = 1);

    public function getTermId(Term $term);

    public function getByKeywords(array $keywords);

    public function getLowFrequencyTerms(array $keywords, $totalDocuments, $cutoffFrequency = 0.01);
}
