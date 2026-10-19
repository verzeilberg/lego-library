<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add lego_user_set_minifig to track missing minifigs per set in a list';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE lego_user_set_minifig (id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)', set_list_set_id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)', set_minifig_id INT NOT NULL, missing_quantity INT NOT NULL, INDEX IDX_7C5E18B21E82FFC3 (set_list_set_id), INDEX IDX_7C5E18B2E28B3C1E (set_minifig_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE lego_user_set_minifig ADD CONSTRAINT FK_7C5E18B21E82FFC3 FOREIGN KEY (set_list_set_id) REFERENCES lego_set_list_set (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lego_user_set_minifig ADD CONSTRAINT FK_7C5E18B2E28B3C1E FOREIGN KEY (set_minifig_id) REFERENCES lego_set_minifig (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE lego_user_set_minifig');
    }
}
