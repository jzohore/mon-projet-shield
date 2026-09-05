<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

/**
 * Le cabinet a acté la fin de la relation d'affaires pour un dossier : le délai
 * de conservation LCB-FT démarre.
 */
final readonly class BusinessRelationshipEndedEvent
{
    public function __construct(
        public string $folderSlugId,
        public string $reason,
        public string $actorName,
        public string $actorSlugId,
        public \DateTimeImmutable $purgeDueAt,
    ) {
    }
}
