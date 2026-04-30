<?php

namespace App\Infrastructure\Notification\Email\Billing;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class DispatchSubscriptionActivatedEmail extends TemplatedEmail
{
    /**
     * @param string $recipientEmail
     * @param string $workspaceName
     */
    public function __construct(
        string $recipientEmail,
        string $workspaceName
    ) {
        parent::__construct();

        $this
            ->to(new Address($recipientEmail))
            ->subject(sprintf('Confirmation d\'activation du Plan Cabinet pour %s', $workspaceName))
            ->htmlTemplate('emails/billing/subscription_activated.html.twig')
            ->context([
                'workspace_name' => $workspaceName,
            ]);
    }
}
