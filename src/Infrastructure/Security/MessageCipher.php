<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

final readonly class MessageCipher
{
    private const string ALGO = 'aes-256-gcm';
    private const int MIN_PAYLOAD_LENGTH = 28;

    public function __construct(
        private string $encryptionKey,
    ) {
    }

    public function encrypt(string $plainText): string
    {
        $iv = random_bytes(12); // IV de 12 octets recommandé pour GCM
        $tag = ''; // Le tag d'authentification sera rempli par openssl_encrypt

        $cipherText = openssl_encrypt(
            $plainText,
            self::ALGO,
            $this->encryptionKey,
            \OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        // On stocke IV (12) + TAG (16) + CIPHERTEXT
        return base64_encode($iv . $tag . $cipherText);
    }

    public function decrypt(string $encodedData): string
    {
        $decoded = base64_decode($encodedData, true);

        // 1. Validation de l'encodage et de la taille minimale du buffer
        if (false === $decoded || strlen($decoded) < self::MIN_PAYLOAD_LENGTH) {
            throw new \RuntimeException('Payload chiffré invalide : encodage corrompu ou structure incomplète.');
        }

        // 2. Extraction sécurisée des composants cryptographiques
        $iv = substr($decoded, 0, 12);
        $tag = substr($decoded, 12, 16);
        $cipherText = substr($decoded, 28);

        // 3. Déchiffrement AES-GCM avec vérification d'authenticité (AEAD)
        $plainText = openssl_decrypt(
            $cipherText,
            self::ALGO,
            $this->encryptionKey,
            \OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if (false === $plainText) {
            throw new \RuntimeException('Déchiffrement impossible : clé invalide ou données altérées.');
        }

        return $plainText;
    }
}
