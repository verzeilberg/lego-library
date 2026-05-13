<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Strip temporary DEFAULT values, finalize NOT NULL columns, re-add media_object FK';
    }

    private function fkExists(string $table, string $fkName): bool
    {
        foreach ($this->connection->createSchemaManager()->listTableForeignKeys($table) as $fk) {
            if ($fk->getName() === $fkName) {
                return true;
            }
        }
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lego_set
            CHANGE total_parts_quantity total_parts_quantity INT NOT NULL,
            CHANGE total_parts total_parts INT NOT NULL,
            CHANGE total_mini_fig_parts total_mini_fig_parts INT NOT NULL');

        $this->addSql('ALTER TABLE lego_set_list_set
            CHANGE instructions instructions TINYINT(1) NOT NULL');

        $this->addSql("ALTER TABLE lego_user_set_part
            CHANGE set_list_set_id set_list_set_id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
            CHANGE discoloured_quantity discoloured_quantity INT NOT NULL");

        if (!$this->fkExists('media_object', 'FK_14D431321E82FFC3')) {
            $this->addSql('ALTER TABLE media_object ADD CONSTRAINT FK_14D431321E82FFC3 FOREIGN KEY (set_list_set_id) REFERENCES lego_set_list_set (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lego_set
            CHANGE total_parts_quantity total_parts_quantity INT NOT NULL DEFAULT 0,
            CHANGE total_parts total_parts INT NOT NULL DEFAULT 0,
            CHANGE total_mini_fig_parts total_mini_fig_parts INT NOT NULL DEFAULT 0');

        $this->addSql('ALTER TABLE lego_set_list_set
            CHANGE instructions instructions TINYINT(1) NOT NULL DEFAULT 1');

        $this->addSql("ALTER TABLE lego_user_set_part
            CHANGE set_list_set_id set_list_set_id CHAR(36) NOT NULL DEFAULT '' COMMENT '(DC2Type:uuid)',
            CHANGE discoloured_quantity discoloured_quantity INT NOT NULL DEFAULT 0");
    }
}
