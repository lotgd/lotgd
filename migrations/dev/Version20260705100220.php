<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260705100220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a tag column to scenes.';
    }

    public function up(Schema $schema): void
    {
        $sceneTable = $schema->getTable('scene');
        $sceneTable->addColumn("tags", Types::JSON)->setNotnull(false);

        $stageTable = $schema->getTable('stage');
        $stageTable->addColumn("properties", Types::JSON)->setNotnull(false);
    }

    public function down(Schema $schema): void
    {
        $sceneTable = $schema->getTable('scene');
        $sceneTable->dropColumn("tags");

        $stageTable = $schema->getTable('stage');
        $stageTable->dropColumn("properties");
    }
}
