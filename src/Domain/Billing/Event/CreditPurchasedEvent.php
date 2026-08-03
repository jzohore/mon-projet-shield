<?php

declare(strict_types=1);

namespace App\Domain\Billing\Event;

use App\Domain\User\Entity\User;
use App\Domain\Wallet\Entity\WalletTransaction;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Contracts\EventDispatcher\Event;

class CreditPurchasedEvent extends Event
{
    public function __construct(
        public User $user,
        public Workspace $workspace,
        public WalletTransaction $transaction,
        public ?string $invoiceUrl = null,
    ) {
    }
}
