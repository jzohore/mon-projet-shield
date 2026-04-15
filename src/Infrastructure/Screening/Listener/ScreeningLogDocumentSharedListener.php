<?php

namespace App\Infrastructure\Screening\Listener;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\Screening\Event\DocumentSharedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
readonly class ScreeningLogDocumentSharedListener
{
    public function __construct(
        private CreateAuditLogUseCase $auditLogUseCase,
    ) {}

    public function __invoke(DocumentSharedEvent $event): void
    {
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::DOCUMENT_SHARED,
            resourceId: $event->workspaceSlugId,
            data: [
                'user_email' => $event->userEmail,
                'audit_id' => $event->auditId,
                'recipients' => $event->recipients,
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }
}
