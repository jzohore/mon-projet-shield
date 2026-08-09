<?php

declare(strict_types=1);

namespace App\Infrastructure\Firm\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Firm\Event\UpdateCounselorSignatureEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogUpdateCounselorSignatureListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(UpdateCounselorSignatureEvent $event): void
    {
        $profile = $event->getProfile();
        $workspace = $profile->workspace;
        $user = $event->getUpdatedBy();

        Assert::notNull($user->id);
        Assert::notNull($workspace);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::SIGNATURE_UPDATED, // 🪄 Changement de type d'événement
            payload: [
                'actor_name' => $user->getFullName(),
                'actor_email' => $user->email,
                'generated_at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
