<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423193301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Encrypt firstName, lastName and pushToken in user_data for GDPR compliance';
    }

    public function up(Schema $schema): void
    {
        $columns = array_keys($this->connection->createSchemaManager()->listTableColumns('user_data'));
        if (!in_array('push_token', $columns, true)) {
            $this->addSql('ALTER TABLE user_data ADD push_token LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:encrypted_string)\'');
        } else {
            $this->addSql('ALTER TABLE user_data CHANGE push_token push_token LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:encrypted_string)\'');
        }
        $this->addSql('ALTER TABLE user_data CHANGE first_name first_name LONGTEXT NOT NULL COMMENT \'(DC2Type:encrypted_string)\', CHANGE last_name last_name LONGTEXT NOT NULL COMMENT \'(DC2Type:encrypted_string)\'');
    }

    public function postUp(Schema $schema): void
    {
        $this->encryptColumns();
    }

    public function preDown(Schema $schema): void
    {
        $this->decryptColumns();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_data CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE last_name last_name VARCHAR(255) NOT NULL, CHANGE push_token push_token VARCHAR(512) DEFAULT NULL');
    }

    private function encryptColumns(): void
    {
        $encKey = sodium_hex2bin($_ENV['EMAIL_ENCRYPTION_KEY'] ?? getenv('EMAIL_ENCRYPTION_KEY'));

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, first_name, last_name, push_token FROM user_data'
        );

        foreach ($rows as $row) {
            $this->connection->executeStatement(
                'UPDATE user_data SET first_name = :fn, last_name = :ln, push_token = :pt WHERE id = :id',
                [
                    'fn' => $this->encrypt($row['first_name'], $encKey),
                    'ln' => $this->encrypt($row['last_name'], $encKey),
                    'pt' => $row['push_token'] !== null ? $this->encrypt($row['push_token'], $encKey) : null,
                    'id' => $row['id'],
                ]
            );
        }
    }

    private function decryptColumns(): void
    {
        $encKey = sodium_hex2bin($_ENV['EMAIL_ENCRYPTION_KEY'] ?? getenv('EMAIL_ENCRYPTION_KEY'));

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, first_name, last_name, push_token FROM user_data'
        );

        foreach ($rows as $row) {
            $this->connection->executeStatement(
                'UPDATE user_data SET first_name = :fn, last_name = :ln, push_token = :pt WHERE id = :id',
                [
                    'fn' => $this->decrypt($row['first_name'], $encKey),
                    'ln' => $this->decrypt($row['last_name'], $encKey),
                    'pt' => $row['push_token'] !== null ? $this->decrypt($row['push_token'], $encKey) : null,
                    'id' => $row['id'],
                ]
            );
        }
    }

    private function encrypt(string $plain, string $encKey): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return sodium_bin2base64(
            $nonce . sodium_crypto_secretbox($plain, $nonce, $encKey),
            SODIUM_BASE64_VARIANT_ORIGINAL
        );
    }

    private function decrypt(string $encoded, string $encKey): string
    {
        try {
            $raw   = sodium_base642bin($encoded, SODIUM_BASE64_VARIANT_ORIGINAL);
            $plain = sodium_crypto_secretbox_open(
                substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
                substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
                $encKey
            );

            return $plain !== false ? $plain : $encoded;
        } catch (\Exception) {
            return $encoded; // already plain text
        }
    }
}
