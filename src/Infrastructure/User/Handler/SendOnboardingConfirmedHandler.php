<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Handler;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Notification\Email\OnboardingCompletedEmail;
use App\Infrastructure\User\Message\SendOnboardingConfirmedMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class SendOnboardingConfirmedHandler
{
    /**
     * @param UserRepositoryInterface $userRepository
     * @param MailerInterface $mailer
     * @param GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MailerInterface $mailer,
        private GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
    ) {}

    /**
     * @param SendOnboardingConfirmedMessage $message
     * @return void
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function __invoke(SendOnboardingConfirmedMessage $message): void
    {
        $user = $this->userRepository->getByEmail($message->userEmail);

        $userId = $user->id;
        Assert::notNull($userId, "L'utilisateur doit avoir un ID pour récupérer le workspace.");
        $workspaceInfo = ($this->getCurrentWorkspaceInfo)($userId);

        $email = new OnboardingCompletedEmail($user->email, $user->firstName, $workspaceInfo->name, $message->url);
        $this->mailer->send($email);
    }
}
