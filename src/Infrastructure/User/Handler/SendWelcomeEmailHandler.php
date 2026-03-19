<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Handler;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Notification\Email\DispatchWelcomeEmail;
use App\Infrastructure\User\Message\SendWelcomeEmailMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class SendWelcomeEmailHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UrlGeneratorInterface $router,
        private MailerInterface $mailer
    ) {}

    public function __invoke(SendWelcomeEmailMessage $message): void
    {
        $user = $this->userRepository->findByEmail($message->userEmail);

        Assert::isInstanceOf($user, User::class);
        // 2. Génération technique de l'URL absolue
        $url = $this->router->generate('app_verify_magic_link', [
            'token' => $user->magicLinkToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $userEmail = $user->email;
        $userFirstName = $user->firstName;
        Assert::notNull($userEmail);
        Assert::notNull($userFirstName);
        // 3. Instanciation et Envoi
        $email = new DispatchWelcomeEmail($user->email, $user->firstName, $url);
        $this->mailer->send($email);
    }
}
