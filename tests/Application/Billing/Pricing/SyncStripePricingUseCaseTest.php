<?php

declare(strict_types=1);

namespace App\Tests\Application\Billing\Pricing;

use App\Application\Billing\UseCase\Pricing\SyncStripePricingUseCase;
use App\Domain\Billing\Entity\PricingPlan;
use App\Domain\Billing\Enum\MinutePack;
use App\Domain\Billing\Enum\Plan;
use App\Domain\Billing\Enum\PricingPlanKind;
use App\Domain\Billing\Repository\PricingPlanRepositoryInterface;
use App\Infrastructure\Service\Payment\Stripe\StripeService;
use PHPUnit\Framework\TestCase;

final class SyncStripePricingUseCaseTest extends TestCase
{
    private int $expectedOfferings;

    protected function setUp(): void
    {
        $this->expectedOfferings = count(Plan::cases()) + count(MinutePack::cases());
    }

    public function testProvisionsEveryMissingOfferingOnStripe(): void
    {
        $repo = $this->createMock(PricingPlanRepositoryInterface::class);
        $repo->method('findByKey')->willReturn(null);
        $repo->expects($this->exactly($this->expectedOfferings))->method('save');

        $stripe = $this->createMock(StripeService::class);
        $stripe->expects($this->exactly($this->expectedOfferings))
            ->method('createPricingPlan')
            ->willReturn(['product_id' => 'prod_x', 'price_id' => 'price_x']);

        $report = (new SyncStripePricingUseCase($repo, $stripe))();

        self::assertCount($this->expectedOfferings, $report);
        foreach ($report as $row) {
            self::assertSame('created', $row['action']);
            self::assertSame('price_x', $row['priceId']);
        }
    }

    public function testDoesNotRecreatePriceForAlreadyProvisionedOffering(): void
    {
        $existing = PricingPlan::create('cabinet_seat', PricingPlanKind::SEAT_SUBSCRIPTION, 'Cabinet', 7900, 150, 2);
        $existing->linkStripe('prod_1', 'price_1');

        $repo = $this->createStub(PricingPlanRepositoryInterface::class);
        $repo->method('findByKey')->willReturn($existing);

        $stripe = $this->createMock(StripeService::class);
        $stripe->expects($this->never())->method('createPricingPlan');

        $report = (new SyncStripePricingUseCase($repo, $stripe))();

        foreach ($report as $row) {
            self::assertSame('updated', $row['action']);
        }
    }
}
