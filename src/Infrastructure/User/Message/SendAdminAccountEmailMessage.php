<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Message;

/**
 * Notifie par e-mail un membre de l'équipe KYSURE d'un changement sur son compte
 * (création, changement de rôle, suspension, réactivation, archivage, suppression).
 */
final readonly class SendAdminAccountEmailMessage
{
    /**
     * @param array<string, scalar|null> $context
     */
    public function __construct(
        public string $recipientEmail,
        public string $firstName,
        public string $action,
        public string $loginUrl,
        public array $context = [],
    ) {
    }
}
