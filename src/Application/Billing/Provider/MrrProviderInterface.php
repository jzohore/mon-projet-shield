<?php

declare(strict_types=1);

namespace App\Application\Billing\Provider;

interface MrrProviderInterface
{
    /**
     * Retourne le Monthly Recurring Revenue (MRR) actuel en centimes ou en euros.
     */
    public function getCurrentMrr(): float;
}
