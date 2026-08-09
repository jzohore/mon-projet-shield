<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email\Kyc;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class ClientKycConfirmationEmail extends TemplatedEmail
{
    public function __construct(
        string $recipientEmail,
        string $recipientFullName,
        string $workspaceName,
        string $folderReference,
    ) {
        parent::__construct();

        $this
            ->to(new Address($recipientEmail, $recipientFullName))
            ->subject('Votre dossier de conformité a bien été soumis')
            ->htmlTemplate('emails/kyc/kyc_submitted_client.html.twig')
            ->context([
                'recipient_name' => $recipientFullName,
                'workspace_name' => $workspaceName,
                'reference' => $folderReference,
            ]);
    }
}
