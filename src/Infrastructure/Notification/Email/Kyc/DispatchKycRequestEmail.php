<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email\Kyc;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class DispatchKycRequestEmail extends TemplatedEmail
{
    public function __construct(
        string $recipientEmail,
        string $recipientFullName,
        string $workspaceName,
        string $folderReference,
        string $actionUrl,
    ) {
        parent::__construct();

        $this
            ->to(new Address($recipientEmail, $recipientFullName))
            ->subject(sprintf('Action requise : Votre dossier de conformité - %s', $workspaceName))
            ->htmlTemplate('emails/kyc/request.html.twig')
            ->context([
                'recipient_name' => $recipientFullName,
                'workspace_name' => $workspaceName,
                'reference' => $folderReference,
                'action_url' => $actionUrl,
                'expires_in' => '7 jours',
            ]);
    }
}
