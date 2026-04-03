<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use LotGD2\Doctrine\MySQLDependentTransactionalTrait;

final class Version20260403091622 extends AbstractMigration
{
    use MySQLDependentTransactionalTrait;

    public function getDescription(): string
    {
        return 'Adds a table for races';
    }

    public function up(Schema $schema): void
    {
        $raceTable = $schema->createTable("race");
        $raceTable->addColumn("id", Types::INTEGER)->setAutoincrement(true)->setNotnull(true);
        $raceTable->addColumn("name", Types::STRING)->setLength(255)->setNotnull(true);
        $raceTable->addColumn("description", Types::TEXT)->setNotnull(true);
        $raceTable->addColumn("selection_text", Types::TEXT)->setNotnull(true);
        $raceTable->addColumn("class_name", Types::STRING)->setLength(255)->setNotnull(true);
        $raceTable->addColumn("configuration", Types::JSONB)->setNotnull(true);

        $raceTable->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames("id")->create());
        $raceTable->addIndex(["name"], "IDX_DA6FBBAF5E237E06");
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable("race");
    }
}
