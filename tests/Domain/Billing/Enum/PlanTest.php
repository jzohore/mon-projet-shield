<?php

declare(strict_types=1);

namespace App\Tests\Domain\Billing\Enum;

use App\Domain\Billing\Enum\Plan;
use App\Domain\Workspace\Enum\WorkspaceType;
use PHPUnit\Framework\TestCase;

final class PlanTest extends TestCase
{
    public function testForWorkspaceTypeMapsFirmToCabinet(): void
    {
        self::assertSame(Plan::CABINET, Plan::forWorkspaceType(WorkspaceType::FIRM));
    }

    public function testForWorkspaceTypeMapsEverythingElseToIndividual(): void
    {
        foreach (WorkspaceType::cases() as $type) {
            if (WorkspaceType::FIRM === $type) {
                continue;
            }
            self::assertSame(Plan::INDIVIDUAL, Plan::forWorkspaceType($type));
        }
    }

    public function testCommercialStructureIsCoherent(): void
    {
        // Le cabinet est facturé moins cher au siège mais impose un minimum de 2.
        self::assertLessThan(
            Plan::INDIVIDUAL->getMonthlyPriceCentsPerSeat(),
            Plan::CABINET->getMonthlyPriceCentsPerSeat(),
        );
        self::assertSame(1, Plan::INDIVIDUAL->getMinSeats());
        self::assertSame(2, Plan::CABINET->getMinSeats());
    }

    public function testOnlyCabinetAllowsCollaborators(): void
    {
        self::assertTrue(Plan::CABINET->allowsCollaborators());
        self::assertFalse(Plan::INDIVIDUAL->allowsCollaborators());
    }

    public function testEveryCaseExposesLabelIncludedMinutesAndStripeKey(): void
    {
        foreach (Plan::cases() as $plan) {
            self::assertNotSame('', $plan->getLabel());
            self::assertGreaterThan(0, $plan->getIncludedMinutesPerSeat());
            self::assertStringStartsWith('STRIPE_PRICE_', $plan->getStripePriceEnvKey());
        }
    }
}
