<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Webmozart\Assert\Assert;

class MagicLinkEmail extends TemplatedEmail
{
    public function __construct(?string $email, string $actionUrl)
    {
        parent::__construct();
        Assert::notNull($email);
        $this
            ->to(new Address(address: $email))
            ->subject('Votre lien de connexion sécurisé')
            ->htmlTemplate('emails/security/magic_link.html.twig')
            ->context([
                'action_url' => $actionUrl,
                'expires_in' => '15 minutes',
            ]);
    }
}
