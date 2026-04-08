<?php

namespace App\Infrastructure\Notification\Email\Billing;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class DispatchPurchaseConfirmationEmail extends TemplatedEmail
{
    public function __construct(
        string $recipientEmail,
        string $workspaceName,
        int $credits,
        ?string $invoiceUrl
    ) {
        parent::__construct();

        $this
            ->to(new Address($recipientEmail))
            ->subject(sprintf('Confirmation d\'achat : +%d crédits pour %s', $credits, $workspaceName))
            ->htmlTemplate('emails/billing/purchase_confirmation.html.twig')
            ->context([
                'workspace_name' => $workspaceName,
                'credits' => $credits,
                'invoice_url' => $invoiceUrl,
            ]);
    }
}
