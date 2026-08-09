<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Repository;

use App\Domain\Wallet\Entity\WalletTransaction;
use Pagerfanta\Pagerfanta;

interface WalletTransactionsRepositoryInterface
{
    /**
     * @return Pagerfanta<WalletTransaction>
     */
    public function getTransactionsList(string $workspaceSlugId): Pagerfanta;
}
