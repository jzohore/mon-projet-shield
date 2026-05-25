<?php

declare(strict_types=1);

namespace App\Infrastructure\Screening\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Screening\Event\DocumentSharedEvent;
use Exception;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class ScreeningLogDocumentSharedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {}

    /**
     * @param DocumentSharedEvent $event
     * @return void
     * @throws Exception
     */
    public function __invoke(DocumentSharedEvent $event): void
    {
        $user = $event->user;
        Assert::notNull($user->id);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::DOCUMENT_SHARED,
            payload: [
                'user_email' => $event->user->email,
                'audit_slug_id' => $event->audit->slugId,
                'recipients' => $event->recipients,
            ],
            actor: $user->id->toString(),
            workspace: $event->workspace
        );

        $this->auditLogRepository->save($audit);
    }
}
