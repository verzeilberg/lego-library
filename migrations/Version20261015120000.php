<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace lego_set_list.public with visibility (public, friends, private)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE lego_set_list ADD visibility VARCHAR(10) DEFAULT 'private' NOT NULL");
        $this->addSql("UPDATE lego_set_list SET visibility = IF(public = 1, 'public', 'private')");
        $this->addSql('ALTER TABLE lego_set_list DROP public');
    }

    public function down(Schema $schema): void
    {
        // Friends-only boards become private, since the old flag can't express them
        $this->addSql('ALTER TABLE lego_set_list ADD public TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql("UPDATE lego_set_list SET public = IF(visibility = 'public', 1, 0)");
        $this->addSql('ALTER TABLE lego_set_list DROP visibility');
    }
}
