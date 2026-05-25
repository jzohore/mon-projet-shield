<?php

namespace App\Infrastructure\Workspace\Handler;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Notification\Email\Workspace\Member\DispatchAccessRevokedEmail;
use App\Infrastructure\Workspace\Message\SendAccessRevokedEmailMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class SendAccessRevokedEmailHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MailerInterface $mailer
    ) {}

    public function __invoke(SendAccessRevokedEmailMessage $message): void
    {
        $userUuid = Uuid::fromString($message->userId);
        $user = $this->userRepository->getById($userUuid);

        $userEmail = $user->email;
        $userFirstName = $user->firstName;

        Assert::notNull($userEmail);
        Assert::notNull($userFirstName);

        $email = new DispatchAccessRevokedEmail(
            recipientEmail: $userEmail,
            firstName: $userFirstName,
            workspaceName: $message->workspaceName
        );

        $this->mailer->send($email);
    }
}
