<?php

namespace App\Infrastructure\Notification\Email\Workspace\Member;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class DispatchAccessRevokedEmail extends TemplatedEmail
{
    public function __construct(
        string $recipientEmail,
        string $firstName,
        string $workspaceName,
    ) {
        parent::__construct();

        $this
            ->to(new Address($recipientEmail))
            ->subject(sprintf('Mise à jour de vos accès : %s', $workspaceName))
            ->htmlTemplate('emails/workspace/member/access_revoked_email.html.twig')
            ->context([
                'first_name' => $firstName,
                'workspace_name' => $workspaceName,
            ]);
    }
}
