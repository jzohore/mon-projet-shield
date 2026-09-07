<?php

declare(strict_types=1);

namespace App\Tests\Application\Billing\Pricing;

use App\Application\Billing\UseCase\Pricing\GetPricingUseCase;
use App\Domain\Billing\Entity\PricingPlan;
use App\Domain\Billing\Enum\PricingPlanKind;
use App\Domain\Billing\Repository\PricingPlanRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\WorkspaceType;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class GetPricingUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    public function testBuildsViewWithRecommendedPlanAndSuggestedSeats(): void
    {
        $workspace = $this->createEntityState(Workspace::class, [
            'id' => Uuid::v7(),
            'type' => WorkspaceType::FIRM,
            'trialDossiersRemaining' => 2,
            'meetingMinutesAllocated' => 90,
            'meetingSecondsConsumed' => 0,
            'subscription' => null,
        ]);

        $cabinet = PricingPlan::create('cabinet_seat', PricingPlanKind::SEAT_SUBSCRIPTION, 'Cabinet', 7900, 150, 2);
        $pack = PricingPlan::create('minutes_300', PricingPlanKind::MINUTE_PACK, 'pack 300', 2900, 300, 1);

        $planRepo = $this->createStub(PricingPlanRepositoryInterface::class);
        $planRepo->method('findByKind')->willReturnMap([
            [PricingPlanKind::SEAT_SUBSCRIPTION, [$cabinet]],
            [PricingPlanKind::MINUTE_PACK, [$pack]],
        ]);

        $memberRepo = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $memberRepo->method('findByWorkspace')->willReturn([1, 2, 3, 4]);

        $workspaceProvider = $this->createStub(CurrentWorkspaceProvider::class);
        $workspaceProvider->method('getWorkspace')->willReturn($workspace);

        $view = (new GetPricingUseCase($planRepo, $memberRepo, $workspaceProvider))();

        self::assertTrue($view->isFirm);
        self::assertFalse($view->hasActiveSubscription);
        self::assertSame('cabinet_seat', $view->recommendedPlanKey);
        self::assertSame(4, $view->suggestedSeats);
        self::assertCount(1, $view->subscriptionPlans);
        self::assertCount(1, $view->minutePacks);
        self::assertSame(90, $view->remainingMeetingMinutes);
    }
}
