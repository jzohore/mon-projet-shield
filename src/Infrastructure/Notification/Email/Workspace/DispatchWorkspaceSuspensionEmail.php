<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email\Workspace;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

final class DispatchWorkspaceSuspensionEmail extends TemplatedEmail
{
    /**
     * @param string $recipientEmail   Email du propriétaire / admin du cabinet
     * @param string $workspaceName    Nom du cabinet suspendu
     * @param string $suspensionReason Raison de la suspension (ex: SIRET invalide, Kbis expiré)
     * @param string $actionUrl        Lien vers la page de paramètres pour débloquer
     */
    public function __construct(
        string $recipientEmail,
        string $workspaceName,
        string $suspensionReason,
        string $actionUrl,
    ) {
        parent::__construct();

        $this
            ->to(new Address($recipientEmail))
            ->subject(sprintf('🚨 Action Requise : Suspension de l\'espace "%s"', $workspaceName))
            ->htmlTemplate('emails/workspace/suspension.html.twig')
            ->context([
                'workspace_name' => $workspaceName,
                'suspension_reason' => $suspensionReason,
                'action_url' => $actionUrl,
                'suspension_date' => new \DateTimeImmutable()->format('d/m/Y'),
            ]);
    }
}
