<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Full schema sync: friendship table, lego_user_set_part refactor, type fixes, drop legacy tables';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTableNames();

        // ── Create friendship table ───────────────────────────────────────────
        if (!in_array('friendship', $tables, true)) {
            $this->addSql("CREATE TABLE friendship (
                id INT AUTO_INCREMENT NOT NULL,
                requester_id INT NOT NULL,
                recipient_id INT NOT NULL,
                status VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_7234A45FED442CF4 (requester_id),
                INDEX IDX_7234A45FE92F8F78 (recipient_id),
                UNIQUE INDEX unique_friendship (requester_id, recipient_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
            $this->addSql('ALTER TABLE friendship ADD CONSTRAINT FK_7234A45FED442CF4 FOREIGN KEY (requester_id) REFERENCES user_data (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE friendship ADD CONSTRAINT FK_7234A45FE92F8F78 FOREIGN KEY (recipient_id) REFERENCES user_data (id) ON DELETE CASCADE');
        }

        // ── Drop legacy book / review tables ─────────────────────────────────
        if (in_array('review', $tables, true)) {
            $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C616A2B381');
            $this->addSql('DROP TABLE review');
        }
        if (in_array('book', $tables, true)) {
            $this->addSql('DROP TABLE book');
        }

        // ── lego_set: make new quantity columns NOT NULL ──────────────────────
        $setColumns = $sm->listTableColumns('lego_set');
        if (isset($setColumns['total_parts_quantity']) && $setColumns['total_parts_quantity']->getNotnull() === false) {
            $this->addSql('ALTER TABLE lego_set CHANGE total_parts_quantity total_parts_quantity INT NOT NULL');
        }
        if (isset($setColumns['total_parts']) && $setColumns['total_parts']->getNotnull() === false) {
            $this->addSql('ALTER TABLE lego_set CHANGE total_parts total_parts INT NOT NULL');
        }
        if (isset($setColumns['total_mini_fig_parts']) && $setColumns['total_mini_fig_parts']->getNotnull() === false) {
            $this->addSql('ALTER TABLE lego_set CHANGE total_mini_fig_parts total_mini_fig_parts INT NOT NULL');
        }

        // ── lego_part_color: add img_url if not yet applied ───────────────────
        $partColorColumns = array_keys($sm->listTableColumns('lego_part_color'));
        if (!in_array('img_url', $partColorColumns, true)) {
            $this->addSql('ALTER TABLE lego_part_color ADD img_url VARCHAR(255) DEFAULT NULL');
        }

        // ── lego_part: drop img_url (moved to lego_part_color) ───────────────
        $partColumns = array_keys($sm->listTableColumns('lego_part'));
        if (in_array('img_url', $partColumns, true)) {
            $this->addSql('ALTER TABLE lego_part DROP COLUMN img_url');
        }

        // ── lego_set_list_set: fix id type + instructions NOT NULL ────────────
        $setListSetColumns = $sm->listTableColumns('lego_set_list_set');
        if (isset($setListSetColumns['id'])) {
            $this->addSql("ALTER TABLE lego_set_list_set CHANGE id id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)'");
        }
        if (isset($setListSetColumns['instructions']) && $setListSetColumns['instructions']->getNotnull() === false) {
            $this->addSql('ALTER TABLE lego_set_list_set CHANGE instructions instructions TINYINT(1) NOT NULL');
        }

        // ── lego_user_set_part: refactor ──────────────────────────────────────
        $userSetPartColumns = array_keys($sm->listTableColumns('lego_user_set_part'));

        // Drop old FK on user_set_id if it exists
        $fks = array_keys($sm->listTableForeignKeys('lego_user_set_part'));
        if (in_array('FK_8BC279BF75A91625', array_map(
            fn($fk) => $fk->getName(),
            $sm->listTableForeignKeys('lego_user_set_part')
        ), true)) {
            $this->addSql('ALTER TABLE lego_user_set_part DROP FOREIGN KEY FK_8BC279BF75A91625');
            $this->addSql('DROP INDEX IDX_8BC279BF75A91625 ON lego_user_set_part');
        }

        // Change id to UUID type
        $this->addSql("ALTER TABLE lego_user_set_part CHANGE id id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)'");

        // Add set_list_set_id if missing
        if (!in_array('set_list_set_id', $userSetPartColumns, true)) {
            $this->addSql("ALTER TABLE lego_user_set_part ADD set_list_set_id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)'");
        }

        // Replace user_set_id with discoloured_quantity (add new, drop old)
        if (in_array('user_set_id', $userSetPartColumns, true) && !in_array('discoloured_quantity', $userSetPartColumns, true)) {
            $this->addSql('ALTER TABLE lego_user_set_part ADD discoloured_quantity INT NOT NULL DEFAULT 0');
            $this->addSql('ALTER TABLE lego_user_set_part DROP COLUMN user_set_id');
        }

        // Add FK and index for set_list_set_id
        if (!in_array('set_list_set_id', $userSetPartColumns, true)) {
            $this->addSql('ALTER TABLE lego_user_set_part ADD CONSTRAINT FK_8BC279BF1E82FFC3 FOREIGN KEY (set_list_set_id) REFERENCES lego_set_list_set (id) ON DELETE CASCADE');
            $this->addSql('CREATE INDEX IDX_8BC279BF1E82FFC3 ON lego_user_set_part (set_list_set_id)');
        }

        // ── media_object: fix set_list_set_id type ────────────────────────────
        $mediaColumns = $sm->listTableColumns('media_object');
        if (isset($mediaColumns['set_list_set_id'])) {
            $this->addSql("ALTER TABLE media_object CHANGE set_list_set_id set_list_set_id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)'");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS friendship');
        $this->addSql('ALTER TABLE lego_part ADD img_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE lego_part_color DROP COLUMN img_url');
    }
}
