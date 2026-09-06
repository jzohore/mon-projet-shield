<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

use App\Domain\User\Enum\ClientRemovalReason;

/**
 * Un client a été retiré du portefeuille d'un cabinet (relation vierge, aucune
 * preuve). Le compte global n'est pas supprimé — le client travaille avec un
 * autre cabinet, ou conserve des dossiers ailleurs.
 *
 * @param list<string> $deletedDraftSlugIds
 */
final readonly class ClientDetachedFromWorkspaceEvent
{
    /**
     * @param list<string> $deletedDraftSlugIds
     */
    public function __construct(
        public string $clientSlugId,
        public string $workspaceSlugId,
        public string $actorName,
        public string $actorSlugId,
        public array $deletedDraftSlugIds,
        public ClientRemovalReason $reason,
        public bool $wasMultiWorkspace,
    ) {
    }
}
