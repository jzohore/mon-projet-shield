<?php

namespace App\Infrastructure\Notification\Email;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

/**
 * Représente l'email physique d'invitation (Infrastructure).
 */
class DispatchInvitationEmail extends TemplatedEmail
{
    public function __construct(
        string $recipientEmail,
        string $workspaceName,
        string $inviterFullName,
        string $roleLabel,
        string $actionUrl
    ) {
        parent::__construct();

        $this
            ->to(new Address($recipientEmail))
            ->subject(sprintf('%s vous a invité(e) sur l\'espace de travail "%s"', $inviterFullName, $workspaceName))
            ->htmlTemplate('emails/workspace/invitation.html.twig')
            ->context([
                'workspace_name' => $workspaceName,
                'inviter_name'   => $inviterFullName,
                'role_label'     => $roleLabel,
                'action_url'     => $actionUrl,
                'expires_in'     => '5 jours',
            ]);
    }
}
