<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260125081356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds columns source_expression and target_expression on scene connections';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('scene_connection');
        $table->addColumn('source_expression', 'string', ['length' => 255])->setNotnull(false);
        $table->addColumn('target_expression', 'string', ['length' => 255])->setNotnull(false);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('scene_connection');
        $table->dropColumn('source_expression');
        $table->dropColumn('target_expression');
    }
}
