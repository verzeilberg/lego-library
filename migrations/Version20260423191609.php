<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423191609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Encrypt email column and add email_hash blind index for GDPR compliance';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON user');
        $this->addSql('ALTER TABLE user ADD email_hash VARCHAR(64) DEFAULT NULL, CHANGE email email LONGTEXT NOT NULL COMMENT \'(DC2Type:encrypted_string)\'');
        // Unique index on email_hash is added in postUp() after data migration
    }

    public function postUp(Schema $schema): void
    {
        // Re-encrypt all existing plain-text emails and populate the hash column
        $this->reEncryptExistingEmails();
        $this->connection->executeStatement('CREATE UNIQUE INDEX UNIQ_8D93D6494E8E423D ON `user` (email_hash)');
    }

    public function preDown(Schema $schema): void
    {
        // Decrypt emails back to plain text before schema is reverted
        $this->decryptExistingEmails();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D6494E8E423D ON `user`');
        $this->addSql('ALTER TABLE `user` DROP email_hash, CHANGE email email VARCHAR(180) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON `user` (email)');
    }

    private function reEncryptExistingEmails(): void
    {
        $encKey  = sodium_hex2bin($_ENV['EMAIL_ENCRYPTION_KEY'] ?? getenv('EMAIL_ENCRYPTION_KEY'));
        $hmacKey = sodium_hex2bin($_ENV['EMAIL_HMAC_KEY']        ?? getenv('EMAIL_HMAC_KEY'));

        $rows = $this->connection->fetchAllAssociative('SELECT id, email FROM `user`');

        foreach ($rows as $row) {
            $plain     = $row['email'];
            $nonce     = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $encrypted = sodium_bin2base64(
                $nonce . sodium_crypto_secretbox($plain, $nonce, $encKey),
                SODIUM_BASE64_VARIANT_ORIGINAL
            );
            $hash = hash_hmac('sha256', strtolower(trim($plain)), $hmacKey);

            $this->connection->executeStatement(
                'UPDATE `user` SET email = :encrypted, email_hash = :hash WHERE id = :id',
                ['encrypted' => $encrypted, 'hash' => $hash, 'id' => $row['id']]
            );
        }
    }

    private function decryptExistingEmails(): void
    {
        $encKey = sodium_hex2bin($_ENV['EMAIL_ENCRYPTION_KEY'] ?? getenv('EMAIL_ENCRYPTION_KEY'));

        $rows = $this->connection->fetchAllAssociative('SELECT id, email FROM `user`');

        foreach ($rows as $row) {
            try {
                $raw       = sodium_base642bin($row['email'], SODIUM_BASE64_VARIANT_ORIGINAL);
                $nonce     = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                $cipher    = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                $plain     = sodium_crypto_secretbox_open($cipher, $nonce, $encKey);

                if ($plain !== false) {
                    $this->connection->executeStatement(
                        'UPDATE `user` SET email = :plain WHERE id = :id',
                        ['plain' => $plain, 'id' => $row['id']]
                    );
                }
            } catch (\Exception) {
                // Already plain text — nothing to do
            }
        }
    }
}
