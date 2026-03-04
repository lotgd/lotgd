<?php
declare(strict_types=1);

namespace LotGD2\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

trait MySQLDependentTransactionalTrait
{
    protected $connection;

    public function isTransactional(): bool
    {
        if ($this->connection->getDatabasePlatform() instanceof AbstractMySqlPlatform) {
            return true;
        }

        return false;
    }
}