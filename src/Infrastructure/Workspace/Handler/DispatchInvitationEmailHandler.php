<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Handler;

use App\Infrastructure\Notification\Email\DispatchInvitationEmail;
use App\Infrastructure\Workspace\Message\DispatchInvitationEmailMessage;
use App\Infrastructure\Workspace\Persistence\WorkspaceInvitationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class DispatchInvitationEmailHandler
{
    public function __construct(
        private WorkspaceInvitationRepository $workspaceInvitationRepository,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $router,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(DispatchInvitationEmailMessage $message): void
    {
        $workspaceSlugId = $message->workspaceSlugId;
        Assert::notNull($workspaceSlugId, 'Le message asynchrone ne contient pas de workspaceSlugId.');

        $invitation = $this->workspaceInvitationRepository->findBySlugId($workspaceSlugId);

        if (!$invitation) {
            $this->logger->info('Envoi annulé : l\'invitation est introuvable.', [
                'workspaceSlugId' => $workspaceSlugId,
            ]);
            return;
        }

        $email = $invitation->email;
        $workspace = $invitation->workspace;
        $owner = $invitation->owner;
        $role = $invitation->invitedRole;

        Assert::notNull($email, 'L\'email de l\'invitation est manquant.');
        Assert::notNull($workspace, 'L\'espace de travail est manquant sur l\'invitation.');
        Assert::notNull($owner, 'L\'auteur de l\'invitation est manquant.');
        Assert::notNull($role, 'Le rôle est manquant sur l\'invitation.');

        $url = $this->router->generate('app_verify_magic_link', [
            'token' => $invitation->magicLinkToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $emailMessage = new DispatchInvitationEmail(
            recipientEmail: $email,
            workspaceName: $workspace->name ?? 'Espace de travail', // Fallback au cas où $name serait aussi nullable
            inviterFullName: $owner->getFullName(),
            roleLabel: $role->getLabel(),
            actionUrl: $url,
        );

        // 6. Envoi
        $this->mailer->send($emailMessage);
    }
}
