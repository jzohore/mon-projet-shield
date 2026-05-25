<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Handler;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Notification\Email\DispatchWelcomeEmail;
use App\Infrastructure\User\Message\SendWelcomeEmailMessage;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class SendWelcomeEmailHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MailerInterface $mailer
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendWelcomeEmailMessage $message): void
    {
        $userUuid = Uuid::fromString($message->userId);
        $user = $this->userRepository->getById($userUuid);

        $userEmail = $user->email;
        $userFirstName = $user->firstName;

        Assert::notNull($userEmail);
        Assert::notNull($userFirstName);

        $email = new DispatchWelcomeEmail($userEmail, $userFirstName, $message->magicLinkUrl);
        $this->mailer->send($email);
    }
}
