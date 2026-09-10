<?php

declare(strict_types=1);

namespace App\Domain\AuditLog\Repository;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\Workspace\Entity\Workspace;
use Pagerfanta\Pagerfanta;

interface AuditLogRepositoryInterface
{
    public function save(AuditLog $auditLog): void;

    /**
     * Récupère tout l'historique d'une ressource spécifique (ex: un utilisateur, un document).
     *
     * @return AuditLog[]
     */
    public function findByResourceId(string $resourceId): array;

    /**
     * Récupère tous les logs d'un type d'événement précis (ex: 'user.created').
     *
     * @return AuditLog[]
     */
    public function findByEventName(string $eventName): array;

    public function findBySlugId(string $slugId): ?AuditLog;

    /**
     * @return Pagerfanta<AuditLog>
     */
    public function getAuditLogsList(Workspace $workspace, ?AuditEventType $eventType = null, ?string $searchQuery = null): Pagerfanta;

    /**
     * Journal inter-cabinets pour le back-office KYSURE (tous événements, aucun
     * filtre de visibilité).
     *
     * @return Pagerfanta<AuditLog>
     */
    public function getGlobalAuditLogsList(
        int $page,
        int $perPage,
        ?string $workspaceQuery = null,
        ?AuditEventType $eventType = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
        ?string $actorQuery = null,
    ): Pagerfanta;

    /**
     * @return AuditLog[]
     */
    public function findLatestLogs(int $limit = 5): array;

    /**
     * Derniers événements visibles côté cabinet, pour l'aperçu du tableau de bord.
     *
     * @return AuditLog[]
     */
    public function findRecentByWorkspace(Workspace $workspace, int $limit = 6): array;
}
