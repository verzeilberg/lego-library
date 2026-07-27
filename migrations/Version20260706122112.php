<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260706122112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE lego_set_list_shared (set_list_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user_data_id INT NOT NULL, INDEX IDX_99F64DCBBA02F06C (set_list_id), INDEX IDX_99F64DCB6FF8BF36 (user_data_id), PRIMARY KEY(set_list_id, user_data_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE lego_set_list_shared ADD CONSTRAINT FK_99F64DCBBA02F06C FOREIGN KEY (set_list_id) REFERENCES lego_set_list (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lego_set_list_shared ADD CONSTRAINT FK_99F64DCB6FF8BF36 FOREIGN KEY (user_data_id) REFERENCES user_data (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE lego_set_list_shared DROP FOREIGN KEY FK_99F64DCBBA02F06C');
        $this->addSql('ALTER TABLE lego_set_list_shared DROP FOREIGN KEY FK_99F64DCB6FF8BF36');
        $this->addSql('DROP TABLE lego_set_list_shared');
    }
}
