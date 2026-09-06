<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

use App\Domain\User\Enum\ClientRemovalReason;

/**
 * Le compte d'un client a été supprimé définitivement : aucun dossier nulle part,
 * client rattaché à un seul cabinet, aucune obligation de conservation LCB-FT.
 * Porté par le journal d'audit du cabinet (le client n'existe plus).
 *
 * @param array{folders_count: int, der_ack_count: int, recordings_count: int, workspaces_count: int} $evidenceCheck
 */
final readonly class ClientAccountDeletedEvent
{
    /**
     * @param array{folders_count: int, der_ack_count: int, recordings_count: int, workspaces_count: int} $evidenceCheck
     */
    public function __construct(
        public string $clientEmail,
        public string $clientCreatedAtIso,
        public string $workspaceSlugId,
        public string $actorName,
        public string $actorSlugId,
        public ClientRemovalReason $reason,
        public array $evidenceCheck,
    ) {
    }
}
