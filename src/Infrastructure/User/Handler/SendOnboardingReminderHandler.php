<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Handler;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Notification\Email\OnboardingRemindedEmail;
use App\Infrastructure\User\Message\SendOnboardingReminderMessage;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class SendOnboardingReminderHandler
{
    /**
     * @param UserRepositoryInterface $userRepository
     * @param UrlGeneratorInterface $router
     * @param MailerInterface $mailer
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UrlGeneratorInterface $router,
        private MailerInterface $mailer
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendOnboardingReminderMessage $message): void
    {
        $user = $this->userRepository->getByEmail($message->userEmail);

        $url = $this->router->generate('app_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL);

        Assert::notNull($user->email);
        Assert::notNull($user->firstName);

        $email = new OnboardingRemindedEmail($user->email, $user->firstName, $url);
        $this->mailer->send($email);
    }
}
