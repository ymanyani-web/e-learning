<?php

namespace App\Repositories;

use PDO;

abstract class BaseRepository
{
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \App\Core\Database::connection();
    }
}
