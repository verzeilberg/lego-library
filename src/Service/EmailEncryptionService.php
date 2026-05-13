<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class EmailEncryptionService
{
    private readonly string $encKey;
    private readonly string $hmacKey;

    public function __construct(
        #[Autowire('%env(EMAIL_ENCRYPTION_KEY)%')] string $encKeyHex,
        #[Autowire('%env(EMAIL_HMAC_KEY)%')] string $hmacKeyHex,
    ) {
        $this->encKey  = sodium_hex2bin($encKeyHex);
        $this->hmacKey = sodium_hex2bin($hmacKeyHex);
    }

    public function encrypt(string $plaintext): string
    {
        $nonce      = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $this->encKey);

        return sodium_bin2base64($nonce . $ciphertext, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    public function decrypt(string $encoded): string
    {
        $raw        = sodium_base642bin($encoded, SODIUM_BASE64_VARIANT_ORIGINAL);
        $nonce      = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain      = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->encKey);

        if ($plain === false) {
            throw new \RuntimeException('Email decryption failed — wrong key or corrupted data');
        }

        return $plain;
    }

    /** Deterministic HMAC used as blind index for DB lookups. */
    public function hash(string $email): string
    {
        return hash_hmac('sha256', strtolower(trim($email)), $this->hmacKey);
    }

    public function looksEncrypted(string $value): bool
    {
        try {
            $raw = sodium_base642bin($value, SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (\SodiumException) {
            return false;
        }

        return strlen($raw) > SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES;
    }
}
