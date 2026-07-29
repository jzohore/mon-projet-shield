<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email\Documents;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class ShareReportEmail extends TemplatedEmail
{
    public function __construct(
        string $recipientEmail,
        string $senderFullName,
        string $subjectName, // Le nom de la personne auditée (ex: "Carlos Ghosn")
        string $actionUrl,
    ) {
        parent::__construct();

        $this
            ->to(new Address($recipientEmail))
            ->subject(sprintf('Rapport de conformité partagé par %s : %s', $senderFullName, $subjectName))
            ->htmlTemplate('emails/document/share_report.html.twig')
            ->context([
                'sender_name' => $senderFullName,
                'subject_name' => $subjectName,
                'action_url' => $actionUrl,
            ]);
    }
}
