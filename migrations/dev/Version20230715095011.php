<?php
declare(strict_types=1);

namespace DoctrineMigrations\Dev;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20230715095011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates characters and users.';
    }

    public function up(Schema $schema): void
    {
        $this->createCharacterTable($schema);
        $this->createUserTable($schema);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable("character");
        $schema->dropTable("user");
    }

    private function createCharacterTable(Schema $schema)
    {
        $table = $schema->createTable("character");
        $table->addColumn("id", Types::INTEGER)
            ->setAutoincrement(true)
            ->setNotnull(true);

        $table->addColumn("name", Types::STRING)->setLength(50)->setNotnull(true);
        $table->addColumn("title", Types::STRING)->setLength(50)->setNotnull(false);
        $table->addColumn("suffix", Types::STRING)->setLength(50)->setNotnull(false);
        $table->addColumn("level", Types::SMALLINT)->setDefault(0)->setNotnull(true);

        $table->setPrimaryKey(["id"]);
        $table->addUniqueIndex(["name"], "UNIQ_937AB0345E237E06");
    }

    private function createUserTable(Schema $schema)
    {
        $table = $schema->createTable("user");
        $table->addColumn("id", Types::INTEGER)
            ->setAutoincrement(true)
            ->setNotnull(true);

        $table->addColumn("name", Types::STRING)->setLength(50)->setNotnull(true);
        $table->addColumn("email", Types::STRING)->setLength(255)->setNotnull(true);
        $table->addColumn("password", Types::STRING)->setLength(255)->setNotnull(true);

        $table->setPrimaryKey(["id"]);
        $table->addUniqueIndex(["name"], "UNIQ_8D93D6495E237E06");
        $table->addUniqueIndex(["email"], "UNIQ_8D93D649E7927C74");
    }
}
