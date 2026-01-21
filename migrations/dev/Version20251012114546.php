<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20251012114546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a role field to user accounts';
    }

    public function up(Schema $schema): void
    {
        $userTable = $schema->getTable("user");
        $userTable
            ->addColumn("roles", Types::JSON)
            ->setDefault('["ROLE_USER"]')
            ->setNotnull(true)
        ;
    }

    public function down(Schema $schema): void
    {
        $userTable = $schema->getTable("user");
        if ($userTable->hasColumn("roles")) {
            $userTable->dropColumn("roles");
        }
    }
}
