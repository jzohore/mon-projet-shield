<?php

namespace App\Infrastructure\Workspace\Listener;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceInvitationCreatedEvent;
use App\Domain\Workspace\Event\WorkspaceInvitationRevokeEvent;
use App\Infrastructure\Workspace\Message\DispatchInvitationEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

final readonly class WorkspaceInvitationFlowListener
{
    public function __construct(
        private CreateAuditLogUseCase $auditLogUseCase,
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $router,
    ) {}
    #[AsEventListener]
    public function auditLog(WorkspaceInvitationCreatedEvent $event): void
    {
        $invitation = $event->workspaceInvitation;
        $user = $invitation->owner;
        Assert::isInstanceOf($user, User::class);
        $workspace = $invitation->workspace;
        Assert::isInstanceOf($workspace, Workspace::class);
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::WORKSPACE_MEMBER_ADDED,
            resourceId: $invitation->slugId,
            data: [
                'workspace_name'   => $workspace->name,
                'created_by_email' => $user->email,
                'email_created' => $invitation->email,
                'role' => $invitation->invitedRole?->getLabel(),
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }

    #[AsEventListener]
    public function dispatchWelcomeEmail(WorkspaceInvitationCreatedEvent $event): void
    {
        $invitation = $event->workspaceInvitation;

        $url = $this->router->generate('portal_user_confirm_token', [
            'token' => $invitation->magicLinkToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $message = new DispatchInvitationEmailMessage(
            $invitation->slugId,
            $url,
        );
        $this->messageBus->dispatch($message);
    }

    #[AsEventListener]
    public function auditRevokeLog(WorkspaceInvitationRevokeEvent $event): void
    {
        $invitation = $event->workspaceInvitation;
        $user = $invitation->owner;
        Assert::isInstanceOf($user, User::class);
        $workspace = $invitation->workspace;
        Assert::isInstanceOf($workspace, Workspace::class);
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::WORKSPACE_MEMBER_REVOKED,
            resourceId: $invitation->slugId,
            data: [
                'workspace_name'   => $workspace->name,
                'revoked_by_email' => $user->email,
                'email_revoked' => $invitation->email,
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }
}
