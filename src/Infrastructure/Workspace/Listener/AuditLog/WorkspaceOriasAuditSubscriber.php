<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener\AuditLog;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Event\WorkspaceOriasCheckFailedEvent;
use App\Domain\Workspace\Event\WorkspaceOriasCheckSucceededEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class WorkspaceOriasAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkspaceOriasCheckSucceededEvent::class => 'onOriasCheckSuccess',
            WorkspaceOriasCheckFailedEvent::class => 'onOriasCheckFailed',
        ];
    }

    public function onOriasCheckSuccess(WorkspaceOriasCheckSucceededEvent $event): void
    {
        $result = $event->oriasResult;

        $auditLog = AuditLog::initiate(
            eventName: AuditEventType::ORIAS_CHECK_SUCCESS,
            payload: [
                'actor_name' => 'Système / Admin',
                'actor_email' => $event->performedByEmail,
                'orias_number' => $result->oriasNumber,
                'legal_name' => $result->legalName,
                'status' => $result->registrationStatus,
                'associations' => $result->associations,
                'categories' => $result->categories,
                'generated_at' => $event->occurredAt->format(\DateTimeInterface::ATOM),
            ],
            workspace: $event->workspace,
        );

        $this->auditLogRepository->save($auditLog);
    }

    public function onOriasCheckFailed(WorkspaceOriasCheckFailedEvent $event): void
    {
        $auditLog = AuditLog::initiate(
            eventName: AuditEventType::ORIAS_CHECK_FAILED,
            payload: [
                'actor_name' => 'Système / Admin',
                'actor_email' => $event->performedByEmail,
                'orias_number' => $event->oriasNumber,
                'error_reason' => $event->errorMessage,
                'generated_at' => $event->occurredAt->format(\DateTimeInterface::ATOM),
            ],
            workspace: $event->workspace,
        );

        $this->auditLogRepository->save($auditLog);
    }
}
