<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Handler;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Infrastructure\Notification\Email\OnboardingCompletedEmail;
use App\Infrastructure\User\Message\SendOnboardingConfirmedMessage;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class SendOnboardingConfirmedHandler
{
    /**
     * @param UserRepositoryInterface $userRepository
     * @param WorkspaceRepositoryInterface $workspaceRepository
     * @param MailerInterface $mailer
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private MailerInterface $mailer,
    ) {}

    /**
     * @param SendOnboardingConfirmedMessage $message
     * @return void
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendOnboardingConfirmedMessage $message): void
    {
        $userUuid = Uuid::fromString($message->userId);
        $user = $this->userRepository->getById($userUuid);

        $workspaceUuid = Uuid::fromString($message->workspaceId);
        $workspaceInfo = $this->workspaceRepository->getById($workspaceUuid);

        $email = new OnboardingCompletedEmail($user->email, $user->firstName, $workspaceInfo->name);
        $this->mailer->send($email);
    }
}
