<?php

namespace App\Infrastructure\Notification\Email;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

/**
 * Représente l'email physique d'invitation (Infrastructure).
 */
class DispatchWelcomeEmail extends TemplatedEmail
{
    public function __construct(
        string $recipientEmail,
        string $firstName,
        string $actionUrl
    ) {
        parent::__construct();

        $this
            ->to(new Address($recipientEmail))
            ->subject('Bienvenue, Finalisez votre inscription')
            ->htmlTemplate('emails/security/welcome_email.html.twig')
            ->context([
                'first_name' => $firstName,
                'action_url' => $actionUrl,
                'expires_in' => '1 heure',
            ]);
    }
}
