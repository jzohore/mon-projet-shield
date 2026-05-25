<?php

namespace App\Application\Audit\UseCase;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Exception;
use Symfony\Component\Uid\Uuid;

readonly class CreateAuditLogUseCase
{
    /**
     * @param AuditLogRepositoryInterface $auditLogRepository
     * @param WorkspaceRepositoryInterface $workspaceRepository
     */
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
    ) {}

    /**
     * @param CreateAuditLogRequest $request
     * @return void
     * @throws Exception
     */
    public function __invoke(CreateAuditLogRequest $request): void
    {
        $workspace = null;
        if ($request->workspaceId) {
            $workspaceUuid = Uuid::fromString($request->workspaceId);
            $workspace = $this->workspaceRepository->getReference($workspaceUuid);
        }
        $audit = AuditLog::initiate(
            eventName: $request->eventName,
            payload: $request->data,
            actor: $request->actorId,
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
