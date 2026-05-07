<?php

namespace App\Infrastructure\Security;

final readonly class MessageCipher
{
    private const ALGO = 'aes-256-gcm';

    public function __construct(
        private string $encryptionKey
    ) {}

    public function encrypt(string $plainText): string
    {
        $iv = random_bytes(12); // IV de 12 octets recommandé pour GCM
        $tag = ''; // Le tag d'authentification sera rempli par openssl_encrypt

        $cipherText = openssl_encrypt(
            $plainText,
            self::ALGO,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        // On stocke IV (12) + TAG (16) + CIPHERTEXT
        return base64_encode($iv . $tag . $cipherText);
    }

    public function decrypt(string $encodedData): string
    {
        $decoded = base64_decode($encodedData);
        $iv = substr($decoded, 0, 12);
        $tag = substr($decoded, 12, 16);
        $cipherText = substr($decoded, 28);

        $plainText = openssl_decrypt(
            $cipherText,
            self::ALGO,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if (false === $plainText) {
            throw new \RuntimeException("Déchiffrement impossible : clé invalide ou données altérées.");
        }

        return $plainText;
    }
}
