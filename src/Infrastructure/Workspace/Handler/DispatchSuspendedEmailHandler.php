<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Handler;

use App\Domain\User\Repository\UserRepositoryInterface;
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
        private UserRepositoryInterface $userRepository,
        private MailerInterface $mailer,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(DispatchSuspendedEmailMessage $message): void
    {
        $workspaceUuid = Uuid::fromString($message->workspaceId);
        Assert::notNull($workspaceUuid, 'Le message asynchrone ne contient pas de invitationUuid.');

        $userUuid = Uuid::fromString($message->userId);
        Assert::notNull($userUuid, 'Le message asynchrone ne contient pas de userId.');

        $workspace = $this->workspaceRepository->getById($workspaceUuid);
        $user = $this->userRepository->getById($userUuid);

        Assert::notNull($workspace, 'L\'espace de travail est manquant sur l\'invitation.');
        Assert::notNull($user, 'L\'auteur de l\'invitation est manquant.');

        $emailMessage = new DispatchWorkspaceSuspensionEmail(
            $user->email,
            $workspace->name,
            $workspace->suspensionReason ?? 'Cessation d\'activité ou radiation constatée sur les registres officiels.',
            $message->actionUrl,
        );

        $this->mailer->send($emailMessage);
    }
}
