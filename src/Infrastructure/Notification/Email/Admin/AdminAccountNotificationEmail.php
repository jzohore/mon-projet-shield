<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email\Admin;

use App\Domain\User\Enum\AdminAccountAction;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class AdminAccountNotificationEmail extends TemplatedEmail
{
    /**
     * @param array<string, scalar|null> $context
     */
    public function __construct(
        string $recipientEmail,
        string $firstName,
        AdminAccountAction $action,
        string $loginUrl,
        array $context = [],
    ) {
        parent::__construct();

        $this
            ->to(new Address($recipientEmail))
            ->subject($this->subjectFor($action))
            ->htmlTemplate('emails/admin/account_notification.html.twig')
            ->context([
                'first_name' => $firstName,
                'action' => $action->value,
                'login_url' => $loginUrl,
                'details' => $context,
            ]);
    }

    private function subjectFor(AdminAccountAction $action): string
    {
        return match ($action) {
            AdminAccountAction::CREATED => 'Un compte a été créé pour vous sur le back-office KYSURE',
            AdminAccountAction::ROLES_CHANGED => 'Votre rôle sur le back-office KYSURE a changé',
            AdminAccountAction::SUSPENDED => 'Votre accès au back-office KYSURE a été suspendu',
            AdminAccountAction::REACTIVATED => 'Votre accès au back-office KYSURE a été rétabli',
            AdminAccountAction::ARCHIVED, AdminAccountAction::DELETED => 'Votre compte du back-office KYSURE a été fermé',
            AdminAccountAction::PROFILE_UPDATED => 'Votre profil KYSURE a été mis à jour',
        };
    }
}
