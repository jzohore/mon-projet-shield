<?php

declare(strict_types=1);

namespace App\Domain\Compliance\ValueObject;

/**
 * Texte exact de l'attestation que le client coche pour accuser réception du DER,
 * avec sa version. Le texte est figé sur chaque {@see \App\Domain\Compliance\Entity\DerAcknowledgement}
 * : une preuve doit dire quel libellé a été accepté.
 *
 * ⚠️ Formulation provisoire — à faire valider par un avocat en droit de la
 * distribution financière (art. 325-5 RG AMF, L.521-2 C. assur.).
 */
final readonly class DerStatement
{
    public const string VERSION = '2026-09-01';

    public const string TEXT = "Je reconnais avoir reçu ce jour le Document d'Entrée en Relation (DER) du cabinet, "
        . 'en avoir pris connaissance, et en conserver une copie sur support durable.';

    public function __construct(
        public string $text = self::TEXT,
        public string $version = self::VERSION,
    ) {
    }

    public static function current(): self
    {
        return new self();
    }
}
