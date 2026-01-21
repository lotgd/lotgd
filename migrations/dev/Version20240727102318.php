<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240727102318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a default flag to scenes to automatically determine the default scene. Replaces longtext column types with json.';
    }

    public function up(Schema $schema): void
    {
        $scene = $schema->getTable('scene');
        $scene->addColumn("default_scene", Types::BOOLEAN)
            ->setDefault(false);
        $scene->getColumn("template_config")->setType(Type::getType("json_document"))->setDefault(null)->setComment("");

        $stage = $schema->getTable('stage');
        $stage->getColumn("action_groups")->setType(Type::getType("json_document"))->setDefault(null)->setComment("");
        $stage->getColumn("attachments")->setType(Type::getType("json_document"))->setDefault(null)->setComment("");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
