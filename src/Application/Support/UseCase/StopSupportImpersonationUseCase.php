<?php

declare(strict_types=1);

namespace App\Application\Support\UseCase;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;

use function Symfony\Component\Clock\now;

/**
 * Fin d'une connexion support : trace la sortie dans le journal d'audit du
 * cabinet à partir du contexte déposé en session au démarrage. Idempotent du
 * point de vue métier : appelé une seule fois par le contrôleur, qui purge
 * ensuite le contexte de session.
 */
final readonly class StopSupportImpersonationUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $context contexte déposé par StartSupportImpersonationUseCase (via le contrôleur)
     */
    public function __invoke(array $context): void
    {
        $slugId = (string) ($context['workspace_slug'] ?? '');
        $workspace = '' !== $slugId ? $this->workspaceRepository->findOneBySlug($slugId) : null;

        $this->auditLogRepository->save(AuditLog::initiate(
            eventName: AuditEventType::ADMIN_IMPERSONATION_EXIT,
            payload: [
                'operator_email' => (string) ($context['operator_email'] ?? ''),
                'operator_name' => (string) ($context['operator_name'] ?? ''),
                'impersonated_email' => (string) ($context['target_email'] ?? ''),
                'impersonated_name' => (string) ($context['target_name'] ?? ''),
                'reason' => (string) ($context['reason'] ?? ''),
                'started_at' => (string) ($context['started_at'] ?? ''),
                'ended_at' => now()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace instanceof Workspace ? $workspace : null,
        ));
    }
}
