<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Handler;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Notification\Email\OnboardingRemindedEmail;
use App\Infrastructure\User\Message\SendOnboardingReminderMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class SendOnboardingReminderHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UrlGeneratorInterface $router,
        private MailerInterface $mailer
    ) {}

    public function __invoke(SendOnboardingReminderMessage $message): void
    {
        $user = $this->userRepository->findByEmail($message->userEmail);

        Assert::isInstanceOf($user, User::class);
        $url = $this->router->generate('app_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = new OnboardingRemindedEmail($user->email, $user->firstName, $url);
        $this->mailer->send($email);
    }
}
