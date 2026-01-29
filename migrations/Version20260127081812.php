<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260127081812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media_object ADD CONSTRAINT FK_14D431321E82FFC3 FOREIGN KEY (set_list_set_id) REFERENCES lego_set_list_set (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_14D431321E82FFC3 ON media_object (set_list_set_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media_object DROP FOREIGN KEY FK_14D431321E82FFC3');
        $this->addSql('DROP INDEX IDX_14D431321E82FFC3 ON media_object');
    }
}
