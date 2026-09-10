<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Payment\Stripe;

use App\Application\Billing\Provider\MrrProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Décore le provider MRR Stripe avec un cache court : l'appel API (itération sur
 * tous les abonnements actifs) ne doit pas partir à chaque rendu de page.
 */
final readonly class CachedMrrProvider implements MrrProviderInterface
{
    private const string CACHE_KEY = 'admin.stripe_mrr';
    private const int TTL_SECONDS = 900;

    public function __construct(
        private MrrProviderInterface $inner,
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {
    }

    public function getCurrentMrr(): float
    {
        try {
            return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): float {
                $item->expiresAfter(self::TTL_SECONDS);

                return $this->inner->getCurrentMrr();
            });
        } catch (\Throwable $exception) {
            $this->logger->warning('MRR Stripe indisponible', ['error' => $exception->getMessage()]);

            return 0.0;
        }
    }
}
