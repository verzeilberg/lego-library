<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261016120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop legacy user_set table (part tracking lives on lego_set_list_set)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_set');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_set (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, set_number VARCHAR(255) NOT NULL, is_complete TINYINT(1) NOT NULL, INDEX IDX_D024457A76ED395 (user_id), INDEX IDX_D02445732D52AC5 (set_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_set ADD CONSTRAINT FK_D024457A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_set ADD CONSTRAINT FK_D02445732D52AC5 FOREIGN KEY (set_number) REFERENCES lego_set (number) ON DELETE CASCADE');
    }
}
