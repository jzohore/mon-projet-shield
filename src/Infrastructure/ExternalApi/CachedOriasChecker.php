<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalApi;

use App\Domain\Workspace\Gateway\OriasCheckerInterface;
use App\Domain\Workspace\ValueObject\OriasStatusResult;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Met en cache les résultats ORIAS DÉFINITIFS (inscrit / non inscrit). Le
 * registre bouge rarement : cela évite un aller-retour HTTP de plusieurs
 * secondes à chaque vérification et ménage le site ORIAS.
 *
 * Un résultat « indisponible » n'est jamais mis en cache : la prochaine
 * demande retente réellement.
 */
final readonly class CachedOriasChecker implements OriasCheckerInterface
{
    private const int TTL_SECONDS = 43_200; // 12 h

    public function __construct(
        private OriasCheckerInterface $inner,
        private CacheInterface $cache,
    ) {
    }

    public function checkNumber(string $oriasNumber): OriasStatusResult
    {
        $key = 'orias_check.' . (preg_replace('/\D+/', '', $oriasNumber) ?: 'invalid');

        return $this->cache->get($key, function (ItemInterface $item) use ($oriasNumber): OriasStatusResult {
            $result = $this->inner->checkNumber($oriasNumber);

            // expiresAfter(0) => résultat non conservé : on retentera au prochain appel.
            $item->expiresAfter($result->isConclusive() ? self::TTL_SECONDS : 0);

            return $result;
        });
    }
}
