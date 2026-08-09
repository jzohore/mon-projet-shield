<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Handler;

use App\Infrastructure\Notification\Email\DispatchInvitationEmail;
use App\Infrastructure\Workspace\Message\DispatchInvitationEmailMessage;
use App\Infrastructure\Workspace\Persistence\WorkspaceInvitationRepository;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class DispatchInvitationEmailHandler
{
    public function __construct(
        private WorkspaceInvitationRepository $workspaceInvitationRepository,
        private MailerInterface $mailer,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(DispatchInvitationEmailMessage $message): void
    {
        $invitationUuid = Uuid::fromString($message->invitationId);
        Assert::notNull($invitationUuid, 'Le message asynchrone ne contient pas de invitationUuid.');

        $invitation = $this->workspaceInvitationRepository->getById($invitationUuid);

        $email = $invitation->email;
        $workspace = $invitation->workspace;
        $owner = $invitation->owner;
        $role = $invitation->invitedRole;

        Assert::notNull($workspace, 'L\'espace de travail est manquant sur l\'invitation.');
        Assert::notNull($owner, 'L\'auteur de l\'invitation est manquant.');

        $emailMessage = new DispatchInvitationEmail(
            recipientEmail: $email,
            workspaceName: $workspace->name ?? 'Espace de travail',
            inviterFullName: $owner->getFullName(),
            roleLabel: $role->getLabel(),
            actionUrl: $message->url,
        );

        $this->mailer->send($emailMessage);
    }
}
