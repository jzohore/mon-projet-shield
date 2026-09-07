<?php

declare(strict_types=1);

namespace App\Domain\Billing\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use function Symfony\Component\Clock\now;

/**
 * Trace d'un événement webhook Stripe déjà traité. Garantit l'idempotence :
 * Stripe peut rejouer le même événement (retries, incidents), on ne veut
 * jamais créditer/activer deux fois.
 */
#[ORM\Entity]
#[ORM\Table(name: 'processed_stripe_events')]
class ProcessedStripeEvent
{
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $processedAt;

    private function __construct(#[ORM\Id]
        #[ORM\Column(type: Types::STRING, length: 255)]
        public private(set) string $eventId)
    {
        $this->processedAt = now();
    }

    public static function record(string $eventId): self
    {
        return new self($eventId);
    }
}
