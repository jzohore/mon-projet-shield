<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email\Compliance;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class DispatchDerRejectedWorkspaceEmail extends TemplatedEmail
{
    public function __construct(
        string $email,
        string $clientName,
        string $folderUrl,
        string $folderRef,
        string $workspaceName,
        string $rejectedAt,
        ?string $declineReason = null,
    ) {
        parent::__construct();

        $this
            ->to(new Address($email))
            ->subject('Action requise : DER refusé - ' . $clientName)
            ->htmlTemplate('emails/compliance/der_rejected_notification.html.twig')
            ->context([
                'client_name' => $clientName,
                'folder_url' => $folderUrl,
                'folder_reference' => $folderRef,
                'workspace_name' => $workspaceName,
                'rejected_at' => $rejectedAt,
                'decline_reason' => $declineReason,
            ]);
    }
}
