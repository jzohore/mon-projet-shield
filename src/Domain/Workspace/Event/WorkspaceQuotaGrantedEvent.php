<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

/**
 * Un administrateur KYSURE a crédité un workspace en dossiers d'essai et/ou en
 * minutes d'entretien. Déclenche l'e-mail au cabinet + le journal d'audit.
 */
final readonly class WorkspaceQuotaGrantedEvent
{
    public function __construct(
        public string $workspaceSlugId,
        public int $dossiersGranted,
        public int $minutesGranted,
        public int $trialDossiersRemaining,
        public int $remainingMinutes,
        public string $reason,
        public string $actorName,
        public string $actorSlugId,
    ) {
    }
}
