<?php

namespace App\Infrastructure\Notification\Email\Billing;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class DispatchSubscriptionCanceledEmail extends TemplatedEmail
{
    /**
     * @param string $recipientEmail
     * @param string $workspaceName
     * @param string $end_date
     */
    public function __construct(
        string $recipientEmail,
        string $workspaceName,
        string $end_date,
    ) {
        parent::__construct();

        $this
            ->to(new Address($recipientEmail))
            ->subject(sprintf('Confirmation de résiliation de votre abonnement pour %s', $workspaceName))
            ->htmlTemplate('emails/billing/subscription_canceled.html.twig')
            ->context([
                'end_date' => $end_date,
            ]);
    }
}
