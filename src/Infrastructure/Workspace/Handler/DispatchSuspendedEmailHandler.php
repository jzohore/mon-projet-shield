<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Handler;

use App\Domain\Shared\Service\GenerateLinkToken;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Infrastructure\Notification\Email\Workspace\DispatchWorkspaceSuspensionEmail;
use App\Infrastructure\Workspace\Message\DispatchSuspendedEmailMessage;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class DispatchSuspendedEmailHandler
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private MailerInterface $mailer,
        private GenerateLinkToken $generateLinkToken,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(DispatchSuspendedEmailMessage $message): void
    {
        $workspaceUuid = Uuid::fromString($message->workspaceId);
        Assert::notNull($workspaceUuid, 'Le message asynchrone ne contient pas de invitationUuid.');

        $workspace = $this->workspaceRepository->getById($workspaceUuid);

        Assert::notNull($workspace, 'L\'espace de travail est manquant sur l\'invitation.');

        $url = $this->generateLinkToken->generateLink(
            routeName: 'app_settings_organization',
            params: ['slugId' => $workspace->slugId],
        );

        Assert::notNull($workspace->email);

        $emailMessage = new DispatchWorkspaceSuspensionEmail(
            $workspace->email,
            $workspace->name,
            $workspace->suspensionReason ?? 'Cessation d\'activité ou radiation constatée sur les registres officiels.',
            $url,
        );

        $this->mailer->send($emailMessage);
    }
}
