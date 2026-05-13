<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notification_preferences JSON column to user_data';
    }

    public function up(Schema $schema): void
    {
        $columns = array_keys($this->connection->createSchemaManager()->listTableColumns('user_data'));
        if (!in_array('notification_preferences', $columns, true)) {
            $this->addSql('ALTER TABLE user_data ADD notification_preferences JSON DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_data DROP COLUMN notification_preferences');
    }
}
