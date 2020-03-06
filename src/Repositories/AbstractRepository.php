<?php

namespace Search\Repositories;

use PDO;

abstract class AbstractRepository
{
    /**
     * @var \PDO
     */
    protected $dbh;

    public function __construct(PDO $databaseHandle)
    {
        $this->dbh = $databaseHandle;
    }
}
