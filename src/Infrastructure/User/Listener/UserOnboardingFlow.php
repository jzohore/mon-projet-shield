<?php

namespace App\Infrastructure\User\Listener;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\User\Entity\User;
use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Event\UserOnboardingCompletedEvent;
use App\Infrastructure\User\Message\SendOnboardingConfirmedMessage;
use App\Infrastructure\User\Message\SendWelcomeEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

final readonly class UserOnboardingFlow
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private CreateAuditLogUseCase $auditLogUseCase,
        private GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo
    ) {}

    /**
     * @throws ExceptionInterface
     */
    #[AsEventListener]
    public function dispatchWelcomeEmail(UserCreatedEvent $event): void
    {
        $message = new SendWelcomeEmailMessage(
            $event->user->email,
        );

        $this->messageBus->dispatch($message);
    }

    #[AsEventListener]
    public function auditLog(UserCreatedEvent $event): void
    {
        $user = $event->user;
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::USER_REGISTERED,
            resourceId: $user->slugId,
            data: [
                'email' => $user->email,
                'first_name' => $user->firstName,
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }

    /**
     * @throws ExceptionInterface
     */
    #[AsEventListener]
    public function dispatchOnboardingConfirmedEmail(UserOnboardingCompletedEvent $event): void
    {
        $user = $event->user;
        Assert::isInstanceOf($user, User::class);
        $workspaceInfo = ($this->getCurrentWorkspaceInfo)($user);
        $message = new SendOnboardingConfirmedMessage(
            $user->email,
            $workspaceInfo->name,
        );

        $this->messageBus->dispatch($message);
    }

    #[AsEventListener]
    public function auditLogOnboardingConfirmed(UserOnboardingCompletedEvent $event): void
    {
        $user = $event->user;
        Assert::isInstanceOf($user, User::class);
        $workspaceInfo = ($this->getCurrentWorkspaceInfo)($user);
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::ONBOARDING_COMPLETED,
            resourceId: $user->slugId,
            data: [
                'email' => $user->email,
                'first_name' => $user->firstName,
                'workspace_name' => $workspaceInfo->name,
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }
}
