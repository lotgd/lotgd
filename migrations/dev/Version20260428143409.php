<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428143409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a table for specialties';
    }

    public function up(Schema $schema): void
    {
        $specialtyTable = $schema->createTable("specialty");
        $specialtyTable->addColumn("id", Types::INTEGER)->setAutoincrement(true)->setNotnull(true);
        $specialtyTable->addColumn("name", Types::STRING)->setLength(255)->setNotnull(true);
        $specialtyTable->addColumn("description", Types::TEXT)->setNotnull(true);
        $specialtyTable->addColumn("selection_text", Types::TEXT)->setNotnull(true);
        $specialtyTable->addColumn("class_name", Types::STRING)->setLength(255)->setNotnull(false);
        $specialtyTable->addColumn("skills", Types::JSONB)->setNotnull(true);
        $specialtyTable->addColumn("configuration", Types::JSONB)->setNotnull(true);

        $specialtyTable->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames("id")->create());
        $specialtyTable->addIndex(["name"], "IDX_E066A6EC5E237E06");
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable("specialty");
    }
}
