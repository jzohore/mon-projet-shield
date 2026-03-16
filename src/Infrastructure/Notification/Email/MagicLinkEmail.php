<?php

namespace App\Infrastructure\Notification\Email;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class MagicLinkEmail extends TemplatedEmail
{
    public function __construct(string $email, string $actionUrl)
    {
        parent::__construct();

        $this
            ->to($email)
            ->subject('Votre lien de connexion sécurisé')
            ->htmlTemplate('emails/security/magic_link.html.twig')
            ->context([
                'action_url' => $actionUrl,
                'expires_in' => '15 minutes',
            ]);
    }
}
