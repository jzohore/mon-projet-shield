<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email\Billing;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class SubscriptionNoticeEmail extends TemplatedEmail
{
    public function __construct(
        string $recipientEmail,
        string $subject,
        string $headline,
        string $body,
        string $workspaceName,
    ) {
        parent::__construct();

        $this
            ->to(new Address($recipientEmail))
            ->subject($subject)
            ->htmlTemplate('emails/billing/subscription_notice.html.twig')
            ->context([
                'headline' => $headline,
                'body' => $body,
                'workspace_name' => $workspaceName,
            ]);
    }
}
