<?php

declare(strict_types=1);

namespace App\Application\Audit\UseCase;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\Uid\Uuid;

readonly class CreateAuditLogUseCase
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
    ) {
    }

    /**
     * @throws \Exception
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
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
