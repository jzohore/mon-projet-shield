<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Event\ClientAccountDeletedEvent;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Trace la suppression d'un compte client. Le client n'existe plus : le journal
 * du cabinet est la seule trace. L'e-mail n'est pas stocké en clair — HMAC avec
 * la clé serveur (un hash nu se casse au dictionnaire). Le bloc `evidence_check`
 * est la pièce maîtresse : il prouve que la garde a bien tourné et que rien de
 * conservable n'a été détruit.
 */
#[AsEventListener]
readonly class AuditLogClientAccountDeletedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private ?string $encryptionKey = null,
    ) {
    }

    public function __invoke(ClientAccountDeletedEvent $event): void
    {
        $workspace = $this->workspaceRepository->findOneBySlug($event->workspaceSlugId);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::CLIENT_ACCOUNT_DELETED,
            payload: [
                'client_email_hmac' => hash_hmac('sha256', mb_strtolower($event->clientEmail), $this->encryptionKey ?? 'kysure'),
                'client_created_at' => $event->clientCreatedAtIso,
                'workspace_slug_id' => $event->workspaceSlugId,
                'workspace_name' => $workspace instanceof Workspace ? $workspace->name : 'N/A',
                'actor_type' => 'workspace_admin',
                'actor_name' => $event->actorName,
                'actor_slug_id' => $event->actorSlugId,
                'reason_code' => $event->reason->value,
                'evidence_check' => $event->evidenceCheck,
                'at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
