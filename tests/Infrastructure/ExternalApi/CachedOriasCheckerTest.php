<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\ExternalApi;

use App\Domain\Workspace\Gateway\OriasCheckerInterface;
use App\Domain\Workspace\ValueObject\OriasStatusResult;
use App\Infrastructure\ExternalApi\CachedOriasChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachedOriasCheckerTest extends TestCase
{
    public function testConclusiveResultIsServedFromCacheOnTheSecondCall(): void
    {
        $inner = new CountingOriasChecker(OriasStatusResult::valid('07001234', 'Inscrit', 'CABINET'));
        $cached = new CachedOriasChecker($inner, new ArrayAdapter());

        $first = $cached->checkNumber('07001234');
        $second = $cached->checkNumber('07 00 12 34'); // même numéro, formatage différent

        self::assertSame(1, $inner->calls, 'Le second appel doit venir du cache.');
        self::assertSame('CABINET', $first->legalName);
        self::assertSame('CABINET', $second->legalName);
    }

    public function testUnavailableResultIsNeverCached(): void
    {
        $inner = new CountingOriasChecker(OriasStatusResult::unavailable('07001234', 'Registre injoignable.'));
        $cached = new CachedOriasChecker($inner, new ArrayAdapter());

        $cached->checkNumber('07001234');
        $cached->checkNumber('07001234');

        self::assertSame(2, $inner->calls, 'Un résultat « indisponible » doit être réessayé.');
    }
}

final class CountingOriasChecker implements OriasCheckerInterface
{
    public int $calls = 0;

    public function __construct(private readonly OriasStatusResult $result)
    {
    }

    public function checkNumber(string $oriasNumber): OriasStatusResult
    {
        ++$this->calls;

        return $this->result;
    }
}
