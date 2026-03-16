<?php

namespace App\Application\Audit\UseCase;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class CreateAuditLogUseCase
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {}

    public function __invoke(CreateAuditLogRequest $request): void
    {
        Assert::notnull($request->eventName);
        Assert::notnull($request->resourceId);
        Assert::isArray($request->data);

        $audit = new AuditLog(
            $request->eventName,
            $request->resourceId,
            $request->data,
        );

        $this->auditLogRepository->save($audit);
    }
}
