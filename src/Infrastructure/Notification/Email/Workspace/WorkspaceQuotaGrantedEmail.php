<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email\Workspace;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class WorkspaceQuotaGrantedEmail extends TemplatedEmail
{
    public function __construct(
        string $email,
        string $workspaceName,
        int $dossiersGranted,
        int $minutesGranted,
        int $trialDossiersRemaining,
        int $remainingMinutes,
    ) {
        parent::__construct();

        $this
            ->to(new Address($email))
            ->subject('Votre quota KYSURE a été rechargé')
            ->htmlTemplate('emails/workspace/quota_granted.html.twig')
            ->context([
                'workspace_name' => $workspaceName,
                'dossiers_granted' => $dossiersGranted,
                'minutes_granted' => $minutesGranted,
                'trial_dossiers_remaining' => $trialDossiersRemaining,
                'remaining_minutes' => $remainingMinutes,
            ]);
    }
}
