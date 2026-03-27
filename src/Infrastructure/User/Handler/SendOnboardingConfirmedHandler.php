<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Handler;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Notification\Email\OnboardingCompletedEmail;
use App\Infrastructure\User\Message\SendOnboardingConfirmedMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class SendOnboardingConfirmedHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UrlGeneratorInterface $router,
        private MailerInterface $mailer,
        private GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
    ) {}

    public function __invoke(SendOnboardingConfirmedMessage $message): void
    {
        $user = $this->userRepository->findByEmail($message->userEmail);

        Assert::isInstanceOf($user, User::class);
        $workspaceInfo = ($this->getCurrentWorkspaceInfo)($user);
        $url = $this->router->generate('app_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = new OnboardingCompletedEmail($user->email, $user->firstName, $workspaceInfo->name, $url);
        $this->mailer->send($email);
    }
}
