<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20250119170641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates an attachment table for modules to register their attachments.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('attachment');
        $table->addColumn("id", Types::INTEGER)
            ->setAutoincrement(true)
            ->setNotnull(true);
        $table->addColumn("name", Types::STRING)
            ->setNotnull(true)
            ->setLength(255);
        $table->addColumn("attachment_class", Types::STRING)
            ->setNotnull(true)
            ->setLength(255);
        $table->setPrimaryKey(["id"]);
        $table->addUniqueIndex(["attachment_class"], "UNIQ_795FD9BB5BD547B6");

        $table = $schema->getTable("scene");
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('attachment');
    }
}
