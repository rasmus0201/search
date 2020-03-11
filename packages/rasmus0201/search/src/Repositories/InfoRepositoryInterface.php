<?php

namespace Search\Repositories;

use PDO;

interface InfoRepositoryInterface
{
    public function setDatabaseHandle(PDO $databaseHandle);

    public function createTableIfNotExists();

    public function getValueByKey($key);

    public function updateByKey($key, $value);
}
