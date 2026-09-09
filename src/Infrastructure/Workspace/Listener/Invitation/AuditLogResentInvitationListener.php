<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener\Invitation;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Event\WorkspaceInvitationResentEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

// L'audit passe avant l'envoi d'e-mail : la trace ne doit pas dépendre du mailer.
#[AsEventListener(priority: 10)]
readonly class AuditLogResentInvitationListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(WorkspaceInvitationResentEvent $event): void
    {
        $invitation = $event->workspaceInvitation;
        $actor = $event->resentBy;
        $workspace = $event->workspace;

        Assert::notNull($actor->id);
        Assert::notNull($actor->email);
        Assert::notNull($workspace->name);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_INVITATION_RESENT,
            payload: [
                'workspace_name' => $workspace->name,
                'invitation_slug_id' => $invitation->slugId,
                'actor_name' => $actor->getFullName(),
                'actor_email' => $actor->email,
                'email_invited' => $invitation->email,
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
