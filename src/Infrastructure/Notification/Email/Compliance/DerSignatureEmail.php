<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email\Compliance;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class DerSignatureEmail extends TemplatedEmail
{
    public function __construct(string $email, string $clientName, string $signatureUrl)
    {
        parent::__construct();

        $this
            ->to(new Address($email))
            ->subject('Votre Document d\'Entrée en Relation')
            ->htmlTemplate('emails/compliance/der_signature.html.twig')
            ->context([
                'client_name' => $clientName,
                'action_url' => $signatureUrl,
            ]);
    }
}
