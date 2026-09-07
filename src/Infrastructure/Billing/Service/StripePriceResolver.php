<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Service;

use App\Domain\Billing\Enum\MinutePack;
use App\Domain\Billing\Enum\Plan;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;

/**
 * Résout un identifiant de prix Stripe (`price_...`) à partir d'une offre du
 * domaine. Les prix sont créés à la main dans le dashboard Stripe et leurs IDs
 * fournis par variables d'environnement (voir Plan::getStripePriceEnvKey()).
 */
final readonly class StripePriceResolver
{
    /** @var array<string, string> clé d'env → id de prix Stripe */
    private array $prices;

    public function __construct(
        #[Autowire(env: 'default::STRIPE_PRICE_INDIVIDUAL_SEAT')]
        ?string $individualSeat = null,
        #[Autowire(env: 'default::STRIPE_PRICE_CABINET_SEAT')]
        ?string $cabinetSeat = null,
        #[Autowire(env: 'default::STRIPE_PRICE_MINUTES_300')]
        ?string $minutes300 = null,
        #[Autowire(env: 'default::STRIPE_PRICE_MINUTES_600')]
        ?string $minutes600 = null,
        #[Autowire(env: 'default::STRIPE_PRICE_MINUTES_1500')]
        ?string $minutes1500 = null,
    ) {
        $this->prices = [
            'STRIPE_PRICE_INDIVIDUAL_SEAT' => $individualSeat ?? '',
            'STRIPE_PRICE_CABINET_SEAT' => $cabinetSeat ?? '',
            'STRIPE_PRICE_MINUTES_300' => $minutes300 ?? '',
            'STRIPE_PRICE_MINUTES_600' => $minutes600 ?? '',
            'STRIPE_PRICE_MINUTES_1500' => $minutes1500 ?? '',
        ];
    }

    public function forPlan(Plan $plan): string
    {
        return $this->resolve($plan->getStripePriceEnvKey());
    }

    public function forMinutePack(MinutePack $pack): string
    {
        return $this->resolve($pack->getStripePriceEnvKey());
    }

    private function resolve(string $envKey): string
    {
        $priceId = $this->prices[$envKey] ?? '';
        Assert::stringNotEmpty(
            $priceId,
            sprintf('Prix Stripe non configuré : renseignez la variable d\'environnement %s.', $envKey),
        );

        return $priceId;
    }
}
