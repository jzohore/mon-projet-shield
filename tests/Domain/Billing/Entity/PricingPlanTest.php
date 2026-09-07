<?php

declare(strict_types=1);

namespace App\Tests\Domain\Billing\Entity;

use App\Domain\Billing\Entity\PricingPlan;
use App\Domain\Billing\Enum\PricingPlanKind;
use PHPUnit\Framework\TestCase;

final class PricingPlanTest extends TestCase
{
    public function testNewPlanIsNotProvisionedUntilStripeIsLinked(): void
    {
        $plan = PricingPlan::create('cabinet_seat', PricingPlanKind::SEAT_SUBSCRIPTION, 'Cabinet', 7900, 150, 2);

        self::assertFalse($plan->isProvisioned());
        self::assertNull($plan->stripePriceId);
        self::assertSame('79,00', $plan->formattedPrice());

        $plan->linkStripe('prod_123', 'price_123');

        self::assertTrue($plan->isProvisioned());
        self::assertSame('price_123', $plan->stripePriceId);
        self::assertSame('prod_123', $plan->stripeProductId);
    }

    public function testUpdatePricingOverwritesCommercialFields(): void
    {
        $plan = PricingPlan::create('minutes_300', PricingPlanKind::MINUTE_PACK, 'pack 300', 2900, 300, 1);

        $plan->updatePricing(3100, 320, 1, 'pack 300 (maj)');

        self::assertSame(3100, $plan->unitAmountCents);
        self::assertSame(320, $plan->meetingMinutes);
        self::assertSame('pack 300 (maj)', $plan->label);
    }

    public function testRejectsNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PricingPlan::create('x', PricingPlanKind::MINUTE_PACK, 'x', -1);
    }
}
