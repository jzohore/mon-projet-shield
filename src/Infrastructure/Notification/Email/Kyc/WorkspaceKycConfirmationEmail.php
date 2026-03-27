<?php

namespace App\Infrastructure\Notification\Email\Kyc;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class WorkspaceKycConfirmationEmail extends TemplatedEmail
{
    public function __construct(
        string $recipientEmail,
        string $recipientFullName,
        string $workspaceName,
        string $companyName,
        string $folderReference,
        string $reviewUrl
    ) {
        parent::__construct();

        $this
            ->to(new Address($recipientEmail, $recipientFullName))
            ->subject(sprintf('Nouveau dossier à réviser : %s', $companyName))
            ->htmlTemplate('emails/kyc/kyc_submitted_workspace.html.twig')
            ->context([
                'recipient_name'  => $recipientFullName,
                'workspace_name'  => $workspaceName,
                'reference'       => $folderReference,
                'company_name'    => $companyName,
                'review_url'   => $reviewUrl,
            ]);
    }
}
