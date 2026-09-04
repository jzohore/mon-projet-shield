<?php

declare(strict_types=1);

namespace App\Infrastructure\DocuSeal;

use Psr\Log\LoggerInterface;

use function Symfony\Component\Clock\now;

/**
 * Authentifie l'origine d'un webhook DocuSeal.
 *
 * Deux mécanismes, selon ce que l'instance DocuSeal déployée expose :
 *  - Cas A : signature HMAC-SHA256 dans l'en-tête `X-Docuseal-Signature`, au
 *    format `{timestamp}.{signature_hex}`. Le corps signé est `{timestamp}.{corps brut}`,
 *    avec une tolérance anti-rejeu de 5 minutes.
 *  - Cas B (fallback) : en-tête statique `X-Kysure-Webhook-Token` comparé en
 *    temps constant à un jeton partagé.
 *
 * `$secrets` accepte une liste séparée par des virgules : on teste chaque valeur,
 * ce qui permet une rotation sans fenêtre de perte.
 *
 * ⚠️ Ce service ne journalise jamais le secret, le jeton ni la signature reçue.
 */
final readonly class DocuSealSignatureVerifier
{
    /** Tolérance sur l'écart d'horodatage de la signature HMAC (secondes). */
    private const int TIMESTAMP_TOLERANCE_SECONDS = 300;

    public function __construct(
        private string $secrets,
        private LoggerInterface $logger,
        private string $sharedToken = '',
        private bool $requireSignature = true,
    ) {
    }

    public function verify(string $rawPayload, ?string $signatureHeader, ?string $tokenHeader): bool
    {
        // Cas B : jeton statique partagé.
        if ('' !== $this->sharedToken && null !== $tokenHeader && '' !== $tokenHeader) {
            return hash_equals($this->sharedToken, $tokenHeader);
        }

        // Aucun élément d'authentification fourni.
        if (null === $signatureHeader || '' === $signatureHeader) {
            if (false === $this->requireSignature) {
                $this->logger->warning('Webhook DocuSeal accepté sans signature (DOCUSEAL_WEBHOOK_REQUIRE_SIGNATURE=0).');

                return true;
            }

            return false;
        }

        [$signedContent, $signature] = $this->splitSignatureHeader($signatureHeader, $rawPayload);

        if (null === $signature) {
            return false;
        }

        foreach (explode(',', $this->secrets) as $secret) {
            $secret = trim($secret);
            if ('' === $secret) {
                continue;
            }

            $expected = hash_hmac('sha256', $signedContent, $secret);
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne [contenu à signer, signature attendue]. La signature vaut `null`
     * si l'en-tête est structurellement invalide (horodatage périmé inclus).
     *
     * @return array{0: string, 1: string|null}
     */
    private function splitSignatureHeader(string $signatureHeader, string $rawPayload): array
    {
        $parts = explode('.', $signatureHeader, 2);

        // Format `{timestamp}.{signature}` (Cas A « standard »).
        if (2 === count($parts) && ctype_digit($parts[0])) {
            $elapsed = abs(now()->getTimestamp() - (int) $parts[0]);
            if ($elapsed > self::TIMESTAMP_TOLERANCE_SECONDS) {
                $this->logger->warning('Webhook DocuSeal : signature horodatée hors tolérance.', [
                    'elapsed_seconds' => $elapsed,
                ]);

                return [$rawPayload, null];
            }

            return [$parts[0] . '.' . $rawPayload, $parts[1]];
        }

        // En-tête = signature brute du corps.
        return [$rawPayload, $signatureHeader];
    }
}
