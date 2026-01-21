<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

final class Version20251015164010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a context column to the sage.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable("stage");
        $table->addColumn("context", "jsonb")
            ->setType(Type::getType("json_document"))
            ->setDefault(null)
            ->setComment("")
            ->setNotnull(false)
        ;
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable("stage");

        if ($table->hasColumn("context")) {
            $table->dropColumn("context");
        }
    }
}
