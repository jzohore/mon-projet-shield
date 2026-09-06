<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email\Client;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class ClientRelationshipEndedEmail extends TemplatedEmail
{
    public function __construct(
        string $email,
        string $clientName,
        string $workspaceName,
        ?string $workspaceContactEmail,
        ?string $purgeDueAtFormatted,
    ) {
        parent::__construct();

        $this
            ->to(new Address($email))
            ->subject(sprintf('Fin de votre relation avec %s', $workspaceName))
            ->htmlTemplate('emails/client/relationship_ended.html.twig')
            ->context([
                'client_name' => $clientName,
                'workspace_name' => $workspaceName,
                'workspace_contact_email' => $workspaceContactEmail,
                'purge_due_at' => $purgeDueAtFormatted,
            ]);
    }
}
