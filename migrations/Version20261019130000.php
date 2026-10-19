<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add BrickLink ids to lego_part and lego_color (for BrickLink wanted list export)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lego_color ADD brick_link_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE lego_part ADD brick_link_id VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lego_color DROP brick_link_id');
        $this->addSql('ALTER TABLE lego_part DROP brick_link_id');
    }
}
