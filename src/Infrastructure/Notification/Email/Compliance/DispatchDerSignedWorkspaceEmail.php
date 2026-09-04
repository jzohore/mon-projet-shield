<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email\Compliance;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class DispatchDerSignedWorkspaceEmail extends TemplatedEmail
{
    public function __construct(string $email, string $clientName, string $folderUrl, string $folderRef, string $workspace_name, string $signedAt)
    {
        parent::__construct();

        $this
            ->to(new Address($email))
            ->subject('DER accusé en réception - ' . $clientName)
            ->htmlTemplate('emails/compliance/der_signed_notification.html.twig')
            ->context([
                'client_name' => $clientName,
                'folder_url' => $folderUrl,
                'folder_reference' => $folderRef,
                'workspace_name' => $workspace_name,
                'signed_at' => $signedAt,
            ]);
    }
}
