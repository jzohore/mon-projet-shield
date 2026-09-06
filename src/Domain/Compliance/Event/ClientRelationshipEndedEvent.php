<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

use App\Domain\Compliance\Enum\RelationshipEndReason;

/**
 * Événement « chapeau » : le cabinet a clôturé sa relation d'affaires avec un
 * client. Chaque dossier concerné émet en plus son propre
 * {@see BusinessRelationshipEndedEvent}. Cet événement porte l'e-mail unique au
 * client et le log d'audit agrégé.
 */
final readonly class ClientRelationshipEndedEvent
{
    /**
     * @param list<string> $endedFolderSlugIds
     * @param list<string> $deletedDraftSlugIds
     */
    public function __construct(
        public string $clientSlugId,
        public string $workspaceSlugId,
        public string $actorName,
        public string $actorSlugId,
        public RelationshipEndReason $reason,
        public array $endedFolderSlugIds,
        public array $deletedDraftSlugIds,
        public ?\DateTimeImmutable $latestPurgeDueAt,
    ) {
    }
}
