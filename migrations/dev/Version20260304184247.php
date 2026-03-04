<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use LotGD2\Doctrine\MySQLDependentTransactionalTrait;

final class Version20260304184247 extends AbstractMigration
{
    use MySQLDependentTransactionalTrait;

    public function getDescription(): string
    {
        return 'Removes description and context fields from stage.';
    }

    public function up(Schema $schema): void
    {
        $stageTable = $schema->getTable("stage");

        $stageTable->dropColumn("description");
        $stageTable->dropColumn("context");
    }

    public function down(Schema $schema): void
    {
        $stageTable = $schema->getTable("stage");

        $stageTable->addColumn("description", Types::TEXT)->setNotnull(true);
        $stageTable->addColumn("context", "jsonb")
            ->setType(Type::getType("json_document"))
            ->setDefault(null)
            ->setComment("")
            ->setNotnull(false)
        ;
    }
}
