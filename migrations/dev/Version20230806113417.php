<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20230806113417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates scenes';
    }

    public function up(Schema $schema): void
    {
        $sceneTable = $schema->createTable("scene");
        $sceneTable->addColumn("id", Types::INTEGER)
            ->setAutoincrement(true)
            ->setNotnull(true);
        $sceneTable->addColumn("title", Types::STRING)->setLength(255)->setNotnull(true);
        $sceneTable->addColumn("description", Types::TEXT)->setNotnull(true);
        $sceneTable->addColumn("template_class", Types::STRING)->setLength(255)->setNotnull(false);
        $sceneTable->addColumn("template_config", Types::JSON)->setNotnull(false)->setComment("(DC2Type:json)");
        $sceneTable->setPrimaryKey(["id"]);

        $sceneActionGroupTable = $schema->createTable("scene_action_group");
        $sceneActionGroupTable->addColumn("id", Types::INTEGER)
            ->setAutoincrement(true)
            ->setNotnull(true);
        $sceneActionGroupTable->addColumn("scene_id", Types::INTEGER)->setNotnull(true);
        $sceneActionGroupTable->addColumn("title", Types::STRING)->setLength(255)->setNotnull(true);
        $sceneActionGroupTable->addColumn("sorting", Types::SMALLINT)->setNotnull(true);
        $sceneActionGroupTable->setPrimaryKey(["id"]);
        $sceneActionGroupTable->addIndex(["scene_id"], "IDX_5F80CAFC166053B4");

        $sceneActionGroupSceneConnectionTable = $schema->createTable("scene_action_group_scene_connection");
        $sceneActionGroupSceneConnectionTable->addColumn("scene_action_group_id", Types::INTEGER)->setNotnull(true);
        $sceneActionGroupSceneConnectionTable->addColumn("scene_connection_id", Types::INTEGER)->setNotnull(true);
        $sceneActionGroupSceneConnectionTable->setPrimaryKey(["scene_action_group_id", "scene_connection_id"]);
        $sceneActionGroupSceneConnectionTable->addIndex(["scene_action_group_id"], "IDX_7F3E4BE8632F33F");
        $sceneActionGroupSceneConnectionTable->addIndex(["scene_connection_id"], "IDX_7F3E4BE890D7C6EC");

        $sceneConnectionTable = $schema->createTable("scene_connection");
        $sceneConnectionTable->addColumn("id", Types::INTEGER)
            ->setAutoincrement(true)
            ->setNotnull(true);
        $sceneConnectionTable->addColumn("source_scene_id", Types::INTEGER)->setNotnull(true);
        $sceneConnectionTable->addColumn("target_scene_id", Types::INTEGER)->setNotnull(true);
        $sceneConnectionTable->addColumn("source_label", Types::STRING)->setNotnull(false)->setLength(255);
        $sceneConnectionTable->addColumn("target_label", Types::STRING)->setNotnull(false)->setLength(255);
        $sceneConnectionTable->addColumn("type", Types::STRING)->setNotnull(true)->setLength(255);
        $sceneConnectionTable->setPrimaryKey(["id"]);
        $sceneConnectionTable->addIndex(["source_scene_id"], "IDX_24F77D6455406566");
        $sceneConnectionTable->addIndex(["target_scene_id"], "IDX_24F77D64CCCB83DD");

        // Foreign keys
        $sceneActionGroupTable->addForeignKeyConstraint("scene", ["scene_id"], ["id"], ["onDelete" => "CASCADE"], "FK_5F80CAFC166053B4");
        $sceneActionGroupSceneConnectionTable->addForeignKeyConstraint("scene_action_group", ["scene_action_group_id"], ["id"], ["onDelete" => "CASCADE"], "FK_7F3E4BE8632F33F");
        $sceneActionGroupSceneConnectionTable->addForeignKeyConstraint("scene_connection", ["scene_connection_id"], ["id"], ["onDelete" => "CASCADE"], "FK_7F3E4BE890D7C6EC");
        $sceneConnectionTable->addForeignKeyConstraint("scene", ["source_scene_id"], ["id"], ["onDelete" => "CASCADE"], "FK_24F77D6455406566");
        $sceneConnectionTable->addForeignKeyConstraint("scene", ["target_scene_id"], ["id"], ["onDelete" => "CASCADE"], "FK_24F77D64CCCB83DD");
    }

    public function down(Schema $schema): void
    {
        $schema->getTable("scene_action_group")->removeForeignKey("FK_5F80CAFC166053B4");
        $schema->getTable("scene_action_group_scene_connection")->removeForeignKey("FK_7F3E4BE8632F33F");
        $schema->getTable("scene_action_group_scene_connection")->removeForeignKey("FK_7F3E4BE890D7C6EC");
        $schema->getTable("scene_connection")->removeForeignKey("FK_24F77D6455406566");
        $schema->getTable("scene_connection")->removeForeignKey("FK_24F77D64CCCB83DD");

        $schema->dropTable("scene");
        $schema->dropTable("scene_action_group");
        $schema->dropTable("scene_action_group_scene_connection");
        $schema->dropTable("scene_connection");
    }
}
