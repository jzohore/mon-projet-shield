<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener\Invitation;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Event\WorkspaceInvitationAcceptedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

/**
 * Trace le moment exact où un tiers obtient l'accès aux dossiers du cabinet.
 * Deux faits distincts sont journalisés : l'invitation est consommée
 * (WORKSPACE_INVITATION_ACCEPTED) et un accès est ouvert (WORKSPACE_MEMBER_ADDED).
 */
#[AsEventListener]
readonly class AuditLogAcceptInvitationListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(WorkspaceInvitationAcceptedEvent $event): void
    {
        $invitation = $event->workspaceInvitation;
        $workspace = $event->workspace;
        $member = $event->newMember;

        Assert::notNull($member->id);
        Assert::notNull($member->email);
        Assert::notNull($workspace->name);

        $payload = [
            'workspace_name' => $workspace->name,
            'invitation_slug_id' => $invitation->slugId,
            'actor_name' => $member->getFullName(),
            'actor_email' => $member->email,
            'invited_by_email' => $invitation->owner->email,
            'role' => $invitation->invitedRole->getLabel(),
            'account' => $event->newAccount ? 'new' : 'existing',
        ];

        $this->auditLogRepository->save(AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_INVITATION_ACCEPTED,
            payload: $payload,
            workspace: $workspace,
        ));

        $this->auditLogRepository->save(AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_MEMBER_ADDED,
            payload: $payload,
            workspace: $workspace,
        ));
    }
}
