<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_spare flag to lego_set_part (spare parts from Rebrickable)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lego_set_part ADD is_spare TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lego_set_part DROP is_spare');
    }
}
