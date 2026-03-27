<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Handler;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Notification\Email\MagicLinkEmail;
use App\Infrastructure\User\Message\SendMagicLinkMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class SendMagicLinkHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MailerInterface $mailer
    ) {}

    public function __invoke(SendMagicLinkMessage $message): void
    {
        // 1. On récupère l'entité fraîche depuis la base de données
        $user = $this->userRepository->findByEmail($message->userEmail);

        Assert::isInstanceOf($user, User::class);

        // 3. Instanciation et Envoi
        $email = new MagicLinkEmail($user->email, $message->magicLinkUrl);
        $this->mailer->send($email);
    }
}
