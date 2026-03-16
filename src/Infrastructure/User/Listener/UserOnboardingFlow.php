<?php

namespace App\Infrastructure\User\Listener;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Event\UserOnboardingCompletedEvent;
use App\Infrastructure\User\Message\SendMagicLinkMessage;
use App\Infrastructure\User\Message\SendOnboardingConfirmedMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class UserOnboardingFlow
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private CreateAuditLogUseCase $auditLogUseCase,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    #[AsEventListener]
    public function dispatchWelcomeEmail(UserCreatedEvent $event): void
    {
        $message = new SendMagicLinkMessage(
            $event->user->email,
            $event->user->magicLinkToken,
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
        $message = new SendOnboardingConfirmedMessage(
            $event->user->email,
            $event->user->firstName,
            $event->user->workspace->name,
        );

        $this->messageBus->dispatch($message);
    }

    #[AsEventListener]
    public function auditLogOnboardingConfirmed(UserOnboardingCompletedEvent $event): void
    {
        $user = $event->user;
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::ONBOARDING_COMPLETED,
            resourceId: $user->slugId,
            data: [
                'email' => $user->email,
                'first_name' => $user->firstName,
                'workspace_name' => $user->workspace->name,
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }
}
