<?php

namespace App\Infrastructure\User\Listener;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Application\User\UseCase\UpdateProfilUseCase;
use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Event\UserOnboardingCompletedEvent;
use App\Infrastructure\Service\Payment\Stripe\StripeService;
use App\Infrastructure\User\Message\SendOnboardingConfirmedMessage;
use App\Infrastructure\User\Message\SendWelcomeEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

final readonly class UserOnboardingFlow
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private CreateAuditLogUseCase $auditLogUseCase,
        private GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
        private UrlGeneratorInterface $router,
        private StripeService $stripeService,
        private UpdateProfilUseCase $updateProfilUseCase,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    #[AsEventListener]
    public function dispatchWelcomeEmail(UserCreatedEvent $event): void
    {
        $magicLinkUrl = $this->router->generate('app_verify_magic_link', [
            'token' => $event->user->magicLinkToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $email = $event->user->email;
        Assert::notNull($magicLinkUrl);
        Assert::notNull($email);
        $message = new SendWelcomeEmailMessage(
            $email,
            $magicLinkUrl,
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
        $url = $this->router->generate('app_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL);
        Assert::notNull($user->email);
        $message = new SendOnboardingConfirmedMessage(
            $user->email,
            $url,
        );

        $this->messageBus->dispatch($message);
    }

    #[AsEventListener]
    public function auditLogOnboardingConfirmed(UserOnboardingCompletedEvent $event): void
    {
        $user = $event->user;
        Assert::notNull($user);
        $userId = $user->id;
        Assert::notNull($userId, "L'utilisateur doit avoir un ID pour récupérer le workspace.");
        $workspaceInfo = ($this->getCurrentWorkspaceInfo)($userId);
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

    #[AsEventListener]
    public function createStripeCustomerEvent(UserOnboardingCompletedEvent $event): void
    {
        $user = $event->user;
        $this->stripeService->createStripeCustomer($user);
        ($this->updateProfilUseCase)($user);
    }
}
