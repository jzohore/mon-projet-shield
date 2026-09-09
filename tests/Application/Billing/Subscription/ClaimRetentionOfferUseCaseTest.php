<?php

declare(strict_types=1);

namespace App\Tests\Application\Billing\Subscription;

use App\Application\Billing\UseCase\Subscription\ClaimRetentionOfferUseCase;
use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Enum\SubscriptionStatus;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Service\Payment\Stripe\StripeService;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class ClaimRetentionOfferUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private Subscription $subscription;
    private StripeService&MockObject $stripeService;
    private SubscriptionRepositoryInterface&MockObject $subscriptionRepository;

    private function useCase(bool $isAdmin = true): ClaimRetentionOfferUseCase
    {
        $this->subscription = $this->createEntityState(Subscription::class, [
            'status' => SubscriptionStatus::ACTIVE,
            'currentPeriodEnd' => new \DateTimeImmutable('+20 days'),
            'stripeSubscriptionId' => 'sub_123',
            'cancelAtPeriodEnd' => false,
        ]);
        $workspace = $this->createEntityState(Workspace::class, ['subscription' => $this->subscription]);

        $workspaceProvider = $this->createStub(CurrentWorkspaceProvider::class);
        $workspaceProvider->method('getWorkspace')->willReturn($workspace);
        $userProvider = $this->createStub(CurrentUserProvider::class);

        $memberRepo = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $memberRepo->method('isUserAdminOfWorkspace')->willReturn($isAdmin);

        $this->stripeService = $this->createMock(StripeService::class);
        $this->subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);

        return new ClaimRetentionOfferUseCase(
            $workspaceProvider,
            $userProvider,
            $memberRepo,
            $this->subscriptionRepository,
            $this->stripeService,
        );
    }

    public function testAppliesTheCouponAndMarksTheOfferClaimed(): void
    {
        $useCase = $this->useCase();

        $this->stripeService->expects($this->once())->method('applyRetentionCoupon')->with('sub_123');
        $this->subscriptionRepository->expects($this->once())->method('save');

        $useCase();

        self::assertFalse($this->subscription->canClaimRetentionOffer());
    }

    public function testRejectsASecondClaim(): void
    {
        $useCase = $this->useCase();
        $this->subscription->claimRetentionOffer();

        $this->stripeService->expects($this->never())->method('applyRetentionCoupon');

        $this->expectException(\DomainException::class);
        $useCase();
    }

    public function testRejectsNonAdmin(): void
    {
        $useCase = $this->useCase(isAdmin: false);

        $this->stripeService->expects($this->never())->method('applyRetentionCoupon');

        $this->expectException(NotWorkspaceAdminException::class);
        $useCase();
    }
}
