<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use LotGD2\Doctrine\MySQLDependentTransactionalTrait;

final class Version20260712084101 extends AbstractMigration
{
    use MySQLDependentTransactionalTrait;

    public function getDescription(): string
    {
        return 'Creates a table for game state';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('game_state');
        $table->addColumn("id", Types::INTEGER)->setAutoincrement(true)->setNotnull(true);
        $table->addColumn("name", Types::STRING)->setLength(255)->setNotnull(true);
        $table->addColumn("state", Types::JSON)->setNotnull(false);
        $table->addColumn("type", Types::STRING)->setNotnull(true)->setLength(255);

        $table->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames("id")->create());
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('game_state');
    }
}
