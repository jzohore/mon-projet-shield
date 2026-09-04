<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Exception;

/**
 * Le lien d'accusé de réception du DER n'est pas exploitable (inconnu, mauvais
 * type, ou expiré). Sert au rendu de la page publique, sans révéler l'existence
 * d'un dossier.
 */
final class AcknowledgementLinkException extends \DomainException
{
    public const string REASON_INVALID = 'invalid';
    public const string REASON_EXPIRED = 'expired';

    private function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function invalid(): self
    {
        return new self(self::REASON_INVALID, 'Lien d\'accusé de réception invalide.');
    }

    public static function expired(): self
    {
        return new self(self::REASON_EXPIRED, 'Ce lien a expiré. Le cabinet doit vous renvoyer le document.');
    }
}
