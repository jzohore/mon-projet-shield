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
        // 1. Validation de la payload asynchrone (Correction Ligne 27)
        $workspaceSlugId = $message->workspaceSlugId;
        Assert::notNull($workspaceSlugId, 'Le message asynchrone ne contient pas de workspaceSlugId.');

        // 2. Récupération idempotente
        $invitation = $this->workspaceInvitationRepository->findBySlugId($workspaceSlugId);

        if (!$invitation) {
            $this->logger->info('Envoi annulé : l\'invitation est introuvable.', [
                'workspaceSlugId' => $workspaceSlugId,
            ]);
            return;
        }

        // 3. Extraction & Type Narrowing massif (Corrections Lignes 42, 54, 55)
        // On stocke dans des variables locales et on valide pour rassurer PHPStan
        $email = $invitation->email;
        $workspace = $invitation->workspace;
        $owner = $invitation->owner;
        $role = $invitation->invitedRole;

        Assert::notNull($email, 'L\'email de l\'invitation est manquant.');
        Assert::notNull($workspace, 'L\'espace de travail est manquant sur l\'invitation.');
        Assert::notNull($owner, 'L\'auteur de l\'invitation est manquant.');
        Assert::notNull($role, 'Le rôle est manquant sur l\'invitation.');

        // 4. Génération de l'URL
        $url = $this->router->generate('app_verify_magic_link', [
            'token' => $invitation->magicLinkToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // 5. Instanciation
        // Grâce aux Asserts ci-dessus, PHPStan sait que $workspace, $owner et $role sont des objets valides.
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
