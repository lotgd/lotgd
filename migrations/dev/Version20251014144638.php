<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

final class Version20251014144638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a properties column to characters to keep additional variables inside.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable("character");
        $table
            ->addColumn("properties", "jsonb")
            ->setType(Type::getType("json_document"))
            ->setDefault(null)
            ->setComment("")
            ->setNotnull(false)
        ;
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable("character");

        if ($table->hasColumn("properties")) {
            $table->dropColumn("properties");
        }
    }
}
