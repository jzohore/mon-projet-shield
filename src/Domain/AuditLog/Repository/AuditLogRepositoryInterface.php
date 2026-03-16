<?php

namespace App\Domain\AuditLog\Repository;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use Pagerfanta\Pagerfanta;

interface AuditLogRepositoryInterface
{
    /**
     * @param AuditLog $auditLog
     * @return void
     */
    public function save(AuditLog $auditLog): void;

    /**
     * Récupère tout l'historique d'une ressource spécifique (ex: un utilisateur, un document).
     * @return AuditLog[]
     */
    public function findByResourceId(string $resourceId): array;

    /**
     * Récupère tous les logs d'un type d'événement précis (ex: 'user.created').
     * @return AuditLog[]
     */
    public function findByEventName(string $eventName): array;

    public function findBySlugId(string $slugId): ?AuditLog;

    /**
     * @param AuditEventType|null $type
     * @return Pagerfanta<AuditLog>
     */
    public function getAuditLogsList(?AuditEventType $type = null): Pagerfanta;
}
