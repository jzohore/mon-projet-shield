<?php

namespace App\Domain\Billing\Event;

use App\Domain\User\Entity\User;
use App\Domain\Wallet\Entity\WalletTransaction;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Contracts\EventDispatcher\Event;

class CreditPurchasedEvent extends Event
{
    public function __construct(
        public Workspace $workspace,
        public WalletTransaction $transaction,
        public User $user,
        public ?string $invoiceUrl = null
    ) {}
}
