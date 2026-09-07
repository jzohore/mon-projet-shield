<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * Demande d'enregistrement d'un utilisateur comme « Customer » Stripe, en tâche
 * de fond. Le handler est idempotent : rejouer ce message ne crée pas de doublon.
 */
#[AsMessage]
final readonly class CreateStripeCustomerMessage
{
    public function __construct(
        public string $userId,
    ) {
    }
}
