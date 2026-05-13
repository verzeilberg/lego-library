<?php

namespace App\Doctrine\Type;

use App\Service\EmailEncryptionService;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class EncryptedStringType extends StringType
{
    public const NAME = 'encrypted_string';

    private static ?EmailEncryptionService $service = null;

    public static function setService(EmailEncryptionService $service): void
    {
        self::$service = $service;
    }

    public static function hasService(): bool
    {
        return self::$service !== null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (self::$service === null) {
            throw new \LogicException('EmailEncryptionService not injected into EncryptedStringType. Ensure DoctrineTypeConfigurator is registered.');
        }

        // Avoid double-encrypting if value was somehow already encrypted
        if (self::$service->looksEncrypted($value)) {
            return $value;
        }

        return self::$service->encrypt($value);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (self::$service === null) {
            throw new \LogicException('EmailEncryptionService not injected into EncryptedStringType. Ensure DoctrineTypeConfigurator is registered.');
        }

        try {
            return self::$service->decrypt($value);
        } catch (\RuntimeException) {
            // Value in DB is not encrypted yet (pre-migration rows) — return as-is
            return $value;
        }
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        // Use TEXT to accommodate variable-length ciphertext
        return $platform->getClobTypeDeclarationSQL($column);
    }
}