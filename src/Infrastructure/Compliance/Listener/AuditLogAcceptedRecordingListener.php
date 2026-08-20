<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Event\AcceptedRecordingEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogAcceptedRecordingListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private UserRepositoryInterface $userRepository,
        private ComplianceFolderRepositoryInterface $complianceFolderRepository,
    ) {
    }

    public function __invoke(AcceptedRecordingEvent $event): void
    {
        $folder = $this->complianceFolderRepository->findOneBySlugId($event->folderSlugId);
        Assert::notNull($folder);

        $workspace = $folder->workspace;

        $user = $this->userRepository->findBySlug($event->userSlugId);
        Assert::notNull($user);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::ACCEPTED_RECORDING, // 🪄 Changement de type d'événement
            payload: [
                'workspace_name' => $workspace->name,
                'actor_name' => $user->getFullName(),
                'actor_email' => $user->email,
                'generated_at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
