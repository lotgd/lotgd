<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221071747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds column paragraphs to the stage table.';
    }

    public function isTransactional(): bool
    {
        if ($this->connection->getDatabasePlatform() instanceof AbstractMySqlPlatform) {
            return true;
        }

        return false;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('stage');
        $table->addColumn("paragraphs", "jsonb")
            ->setType(Type::getType("json_document"))
            ->setDefault(null)
            ->setComment("")
            ->setNotnull(false);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('stage');
        $table->dropColumn("paragraphs");
    }
}
