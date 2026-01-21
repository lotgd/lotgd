<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107065405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the table for masters';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable("master");
        $table->addColumn("id", Types::INTEGER)->setAutoincrement(true)->setNotnull(true);
        $table->addColumn("name", Types::STRING)->setLength(255)->setNotnull(true);
        $table->addColumn("level", Types::SMALLINT)->setNotnull(true);
        $table->addColumn("weapon", Types::STRING)->setLength(255)->setNotnull(true);
        $table->addColumn("text_defeated", Types::STRING)->setLength(255)->setNotnull(true);
        $table->addColumn("text_lost", Types::STRING)->setLength(255)->setNotnull(false);
        $table->addColumn("health", Types::INTEGER)->setNotnull(true);
        $table->addColumn("attack", Types::INTEGER)->setNotnull(true);
        $table->addColumn("defense", Types::INTEGER)->setNotnull(true);

        $table->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames("id")->create());
        $table->addIndex(["level"], "IDX_2D09A3D69AEACC13");
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable("master");
    }
}
