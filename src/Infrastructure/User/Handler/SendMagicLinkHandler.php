<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Handler;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Notification\Email\MagicLinkEmail;
use App\Infrastructure\User\Message\SendMagicLinkMessage;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class SendMagicLinkHandler
{
    /**
     * @param UserRepositoryInterface $userRepository
     * @param MailerInterface $mailer
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MailerInterface $mailer
    ) {}

    /**
     * @param SendMagicLinkMessage $message
     * @return void
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendMagicLinkMessage $message): void
    {
        $user = $this->userRepository->getByEmail($message->userEmail);

        Assert::notNull($user->email);

        $email = new MagicLinkEmail($user->email, $message->magicLinkUrl);
        $this->mailer->send($email);
    }
}
