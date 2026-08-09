<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email\Compliance;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class DerSignedConfirmationClientEmail extends TemplatedEmail
{
    public function __construct(string $email, string $clientName, string $loginPageUrl, string $workspace_name)
    {
        parent::__construct();

        $this
            ->to(new Address($email))
            ->subject('Confirmation de signature de votre DER')
            ->htmlTemplate('emails/compliance/der_signed_confirmation.html.twig')
            ->context([
                'client_name' => $clientName,
                'kysure_login_url' => $loginPageUrl,
                'workspace_name' => $workspace_name,
            ]);
    }
}
