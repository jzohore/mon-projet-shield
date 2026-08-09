<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener\AuditLog;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Event\WorkspaceSiretCheckFailedEvent;
use App\Domain\Workspace\Event\WorkspaceSiretVerifiedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class WorkspaceSiretAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkspaceSiretVerifiedEvent::class => 'onSiretCheckSuccess',
            WorkspaceSiretCheckFailedEvent::class => 'onSiretCheckFailed',
        ];
    }

    public function onSiretCheckSuccess(WorkspaceSiretVerifiedEvent $event): void
    {
        $auditLog = AuditLog::initiate(
            eventName: AuditEventType::SIRET_CHECK_SUCCESS,
            payload: [
                'actor_name' => 'Système / Admin',
                'actor_email' => $event->performedByEmail,
                'result_message' => $event->messageResult,
                'generated_at' => $event->occurredAt->format(\DateTimeInterface::ATOM),
            ],
            workspace: $event->workspace,
        );

        $this->auditLogRepository->save($auditLog);
    }

    public function onSiretCheckFailed(WorkspaceSiretCheckFailedEvent $event): void
    {
        $auditLog = AuditLog::initiate(
            eventName: AuditEventType::SIRET_CHECK_FAILED,
            payload: [
                'actor_name' => 'Système / Admin',
                'actor_email' => $event->performedByEmail,
                'siret_number' => $event->siretNumber,
                'error_reason' => $event->errorMessage,
                'generated_at' => $event->occurredAt->format(\DateTimeInterface::ATOM),
            ],
            workspace: $event->workspace,
        );

        $this->auditLogRepository->save($auditLog);
    }
}
