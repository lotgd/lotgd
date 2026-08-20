<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use LotGD2\Doctrine\MySQLDependentTransactionalTrait;

final class Version20260820150132 extends AbstractMigration
{
    use MySQLDependentTransactionalTrait;

    public function getDescription(): string
    {
        return 'Creates a table for titles';
    }
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('title');
        $table->addColumn("id", Types::INTEGER)->setAutoincrement(true)->setNotnull(true);
        $table->addColumn("dk", Types::SMALLINT)->setNotnull(true);
        $table->addColumn("male", Types::STRING)->setLength(255);
        $table->addColumn("female", Types::STRING)->setLength(255);
        $table->addColumn("other", Types::STRING)->setLength(255);

        $table->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames("id")->create());
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable("title");
    }
}
