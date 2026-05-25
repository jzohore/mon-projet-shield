<?php

namespace App\Infrastructure\User\Listener\Onboarding;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\User\Event\UserOnboardingCompletedEvent;
use Exception;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener(event: UserOnboardingCompletedEvent::class)]
readonly class AuditLogOnboardingConfirmedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(UserOnboardingCompletedEvent $event): void
    {
        $user = $event->user;
        $workspace = $event->workspace;

        Assert::notNull($user->id);
        Assert::notNull($workspace->name);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::ONBOARDING_COMPLETED,
            payload: [
                'email' => $user->email,
                'first_name' => $user->firstName,
                'workspace_name' => $workspace->name,
            ],
            actor: $user->id->toString(),
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
