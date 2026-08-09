<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Handler;

use App\Domain\Shared\Service\GenerateLinkToken;
use App\Infrastructure\Notification\Email\DispatchWelcomeEmail;
use App\Infrastructure\User\Message\SendWelcomeEmailMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendWelcomeEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private GenerateLinkToken $generateLinkToken,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendWelcomeEmailMessage $message): void
    {
        $magicLinkUrl = $this->generateLinkToken->generate(
            routeName: 'app_verify_magic_link',
            magicLinkToken: $message->magicLinkToken
        );

        $email = new DispatchWelcomeEmail(
            recipientEmail: $message->email,
            firstName: $message->fullName,
            actionUrl: $magicLinkUrl
        );

        $this->mailer->send($email);

        $this->logger->info('L\'email de bienvenue a bien été envoyé.', [
            'email' => $message->email,
            'user_id' => $message->userId,
        ]);
    }
}
