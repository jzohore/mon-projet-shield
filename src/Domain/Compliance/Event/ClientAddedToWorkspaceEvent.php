<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

/**
 * Un client a été ajouté au portefeuille d'un cabinet, soit créé, soit rattaché
 * s'il existait déjà dans KYSURE (autre cabinet).
 */
final readonly class ClientAddedToWorkspaceEvent
{
    public function __construct(
        public string $clientSlugId,
        public string $workspaceSlugId,
        public string $actorName,
        public string $actorSlugId,
        public bool $wasCreated,
    ) {
    }
}
