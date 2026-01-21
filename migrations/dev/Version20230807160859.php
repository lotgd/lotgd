<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20230807160859 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a stage table for the current scene that a character sees.';
    }

    public function up(Schema $schema): void
    {
        $stageTable = $schema->createTable("stage");
        $stageTable->addColumn("id", Types::INTEGER)
            ->setAutoincrement(true)
            ->setNotnull(true);
        $stageTable->addColumn("owner_id", Types::INTEGER)->setNotnull(true);
        $stageTable->addColumn("scene_id", Types::INTEGER)->setNotnull(false);
        $stageTable->addColumn("title", Types::STRING)->setLength(255)->setNotnull(true);
        $stageTable->addColumn("description", Types::TEXT)->setNotnull(true);
        $stageTable->addColumn("action_groups", Types::JSON)->setComment("(DC2Type:json_object)")->setNotnull(true);
        $stageTable->addColumn("attachments", Types::JSON)->setComment("(DC2Type:json)")->setNotnull(false);

        $stageTable->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames("id")->create());
        $stageTable->addUniqueIndex(["owner_id"], "UNIQ_C27C93697E3C61F9");
        $stageTable->addIndex(["scene_id"], "IDX_C27C9369166053B4");

        $stageTable->addForeignKeyConstraint("`character`", ["owner_id"], ["id"], ["onDelete" => "CASCADE"], "FK_C27C93697E3C61F9");
        $stageTable->addForeignKeyConstraint("scene", ["scene_id"], ["id"], ["onDelete" => "SET NULL"], "FK_C27C9369166053B4");
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable("stage");
        $table->dropForeignKey("FK_C27C93697E3C61F9");
        $table->dropForeignKey("FK_C27C9369166053B4");

        $schema->dropTable("stage");
    }
}
