<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add all columns missing from production: lego_set, lego_set_list_set, user_data, lego_part_color';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        // ── lego_set ─────────────────────────────────────────────────────────
        $setColumns = array_keys($sm->listTableColumns('lego_set'));

        if (!in_array('base_number', $setColumns, true)) {
            $this->addSql("ALTER TABLE lego_set ADD base_number VARCHAR(20) NOT NULL DEFAULT ''");
        }
        if (!in_array('total_parts', $setColumns, true)) {
            $this->addSql('ALTER TABLE lego_set ADD total_parts INT NOT NULL DEFAULT 0');
        }
        if (!in_array('total_mini_fig_parts', $setColumns, true)) {
            $this->addSql('ALTER TABLE lego_set ADD total_mini_fig_parts INT NOT NULL DEFAULT 0');
        }

        // ── lego_set_list_set ─────────────────────────────────────────────────
        $setListSetColumns = array_keys($sm->listTableColumns('lego_set_list_set'));

        if (!in_array('complete', $setListSetColumns, true)) {
            $this->addSql('ALTER TABLE lego_set_list_set ADD complete TINYINT(1) NOT NULL DEFAULT 1');
        }

        // ── user_data ─────────────────────────────────────────────────────────
        $userDataColumns = array_keys($sm->listTableColumns('user_data'));

        if (!in_array('user_name', $userDataColumns, true)) {
            $this->addSql('ALTER TABLE user_data ADD user_name VARCHAR(255) DEFAULT NULL');
        }
        if (!in_array('bio', $userDataColumns, true)) {
            $this->addSql('ALTER TABLE user_data ADD bio VARCHAR(1024) DEFAULT NULL');
        }
        if (!in_array('updated_at', $userDataColumns, true)) {
            $this->addSql('ALTER TABLE user_data ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }

        // ── lego_part_color ───────────────────────────────────────────────────
        $partColorColumns = array_keys($sm->listTableColumns('lego_part_color'));

        if (!in_array('img_url', $partColorColumns, true)) {
            $this->addSql('ALTER TABLE lego_part_color ADD img_url VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lego_set DROP COLUMN base_number, DROP COLUMN total_parts, DROP COLUMN total_mini_fig_parts');
        $this->addSql('ALTER TABLE lego_set_list_set DROP COLUMN complete');
        $this->addSql('ALTER TABLE user_data DROP COLUMN user_name, DROP COLUMN bio, DROP COLUMN updated_at');
        $this->addSql('ALTER TABLE lego_part_color DROP COLUMN img_url');
    }
}
