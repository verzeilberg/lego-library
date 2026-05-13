<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513152000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing columns: instructions (lego_set_list_set) and total_parts_quantity (lego_set)';
    }

    public function up(Schema $schema): void
    {
        $setListSetColumns = array_keys($this->connection->createSchemaManager()->listTableColumns('lego_set_list_set'));
        if (!in_array('instructions', $setListSetColumns, true)) {
            $this->addSql('ALTER TABLE lego_set_list_set ADD instructions TINYINT(1) NOT NULL DEFAULT 1');
        }

        $setColumns = array_keys($this->connection->createSchemaManager()->listTableColumns('lego_set'));
        if (!in_array('total_parts_quantity', $setColumns, true)) {
            $this->addSql('ALTER TABLE lego_set ADD total_parts_quantity INT NOT NULL DEFAULT 0');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lego_set_list_set DROP COLUMN instructions');
        $this->addSql('ALTER TABLE lego_set DROP COLUMN total_parts_quantity');
    }
}
