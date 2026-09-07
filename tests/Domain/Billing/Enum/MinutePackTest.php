<?php

declare(strict_types=1);

namespace App\Tests\Domain\Billing\Enum;

use App\Domain\Billing\Enum\MinutePack;
use PHPUnit\Framework\TestCase;

final class MinutePackTest extends TestCase
{
    public function testMinutesAndPriceGrowWithPackSize(): void
    {
        self::assertSame(300, MinutePack::SMALL->getMinutes());
        self::assertSame(600, MinutePack::MEDIUM->getMinutes());
        self::assertSame(1500, MinutePack::LARGE->getMinutes());

        self::assertLessThan(MinutePack::MEDIUM->getPriceCents(), MinutePack::SMALL->getPriceCents());
        self::assertLessThan(MinutePack::LARGE->getPriceCents(), MinutePack::MEDIUM->getPriceCents());
    }

    public function testLargerPacksHaveBetterUnitPrice(): void
    {
        $unit = static fn (MinutePack $p): float => $p->getPriceCents() / $p->getMinutes();

        self::assertLessThanOrEqual($unit(MinutePack::SMALL), $unit(MinutePack::MEDIUM));
        self::assertLessThanOrEqual($unit(MinutePack::MEDIUM), $unit(MinutePack::LARGE));
    }

    public function testLabelMentionsTheMinuteCount(): void
    {
        self::assertSame('300 minutes', MinutePack::SMALL->getLabel());
    }

    public function testEveryCaseHasAStripePriceKey(): void
    {
        foreach (MinutePack::cases() as $pack) {
            self::assertSame($pack->value, $pack->getPricingKey());
        }
    }
}
