<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Pricing;

use App\Application\Billing\DTO\Response\PricingView;
use App\Domain\Billing\Enum\Plan;
use App\Domain\Billing\Enum\PricingPlanKind;
use App\Domain\Billing\Repository\PricingPlanRepositoryInterface;
use App\Domain\Workspace\Enum\WorkspaceType;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;

readonly class GetPricingUseCase
{
    public function __construct(
        private PricingPlanRepositoryInterface $pricingPlanRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
    ) {
    }

    public function __invoke(): PricingView
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        $recommendedPlan = Plan::forWorkspaceType($workspace->isFirm() ? WorkspaceType::FIRM : WorkspaceType::INDIVIDUAL);

        $teamSize = \count($this->workspaceMemberRepository->findByWorkspace($workspace->id?->toString() ?? ''));

        return new PricingView(
            subscriptionPlans: $this->pricingPlanRepository->findByKind(PricingPlanKind::SEAT_SUBSCRIPTION),
            minutePacks: $this->pricingPlanRepository->findByKind(PricingPlanKind::MINUTE_PACK),
            isFirm: $workspace->isFirm(),
            hasActiveSubscription: $workspace->hasActiveSubscription(),
            recommendedPlanKey: $recommendedPlan->getPricingKey(),
            suggestedSeats: max($recommendedPlan->getMinSeats(), $teamSize),
            trialDossiersRemaining: $workspace->trialDossiersRemaining,
            remainingMeetingMinutes: $workspace->remainingMeetingMinutes(),
            meetingMinutesAllocated: $workspace->meetingMinutesAllocated,
        );
    }
}
