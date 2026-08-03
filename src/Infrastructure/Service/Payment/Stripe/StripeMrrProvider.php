<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Payment\Stripe;

use App\Application\Billing\Provider\MrrProviderInterface;
use Stripe\Stripe;
use Stripe\Subscription;

final readonly class StripeMrrProvider implements MrrProviderInterface
{
    public function __construct(
        private string $stripeSecretKey,
    ) {
    }

    public function getCurrentMrr(): float
    {
        // 💡 Logique métier Stripe :
        // Stripe ne donne pas de endpoint "get MRR" direct.
        // Il faut généralement récupérer les "Subscriptions" actives ou utiliser Stripe Sigma/Billing.
        // Pour l'exemple, voici comment on pourrait itérer sur les abonnements actifs :

        /*
         *
        $activeSubscriptions = $this->stripeClient->subscriptions->all([
            'status' => 'active',
            'limit' => 100, // Attention à la pagination en production (auto-paging)
        ]);

        $mrrCents = 0;
        foreach ($activeSubscriptions->autoPagingIterator() as $sub) {
            foreach ($sub->items->data as $item) {
                // On ajoute le prix (en centimes)
                $mrrCents += $item->price->unit_amount * $item->quantity;
            }
        }

        return $mrrCents / 100; // Conversion en Euros
        */

        Stripe::setApiKey($this->stripeSecretKey);
        $activeSubscriptions = Subscription::all([
            'status' => 'active',
            'limit' => 100, // Attention à la pagination en production (auto-paging)
        ]);

        $mrrCents = 0;
        foreach ($activeSubscriptions->autoPagingIterator() as $sub) {
            foreach ($sub->items->data as $item) {
                // On ajoute le prix (en centimes)
                $mrrCents += $item->price->unit_amount * $item->quantity;
            }
        }

        // Pour éviter de spammer l'API Stripe à chaque chargement du dashboard en dev :
        return $mrrCents / 100; // Valeur simulée en attendant la vraie implémentation
    }
}
