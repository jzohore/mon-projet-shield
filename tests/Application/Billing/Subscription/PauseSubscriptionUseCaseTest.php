<?php

declare(strict_types=1);

namespace App\Tests\Application\Billing\Subscription;

use App\Application\Billing\UseCase\Subscription\PauseSubscriptionUseCase;
use App\Application\Billing\UseCase\Subscription\ResumeSubscriptionUseCase;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PauseSubscriptionUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private Subscription $subscription;
    private StripeService&MockObject $stripeService;

    /**
     * @return array{PauseSubscriptionUseCase, ResumeSubscriptionUseCase}
     */
    private function useCases(bool $isAdmin = true, bool $paused = false): array
    {
        $this->subscription = $this->createEntityState(Subscription::class, [
            'status' => SubscriptionStatus::ACTIVE,
            'currentPeriodEnd' => new \DateTimeImmutable('+20 days'),
            'stripeSubscriptionId' => 'sub_123',
            'pausedAt' => $paused ? new \DateTimeImmutable() : null,
        ]);
        $workspace = $this->createEntityState(Workspace::class, ['subscription' => $this->subscription]);

        $workspaceProvider = $this->createStub(CurrentWorkspaceProvider::class);
        $workspaceProvider->method('getWorkspace')->willReturn($workspace);
        $userProvider = $this->createStub(CurrentUserProvider::class);
        $memberRepo = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $memberRepo->method('isUserAdminOfWorkspace')->willReturn($isAdmin);

        $this->stripeService = $this->createMock(StripeService::class);
        $repo = $this->createStub(SubscriptionRepositoryInterface::class);

        return [
            new PauseSubscriptionUseCase($workspaceProvider, $userProvider, $memberRepo, $repo, $this->stripeService),
            new ResumeSubscriptionUseCase($workspaceProvider, $userProvider, $memberRepo, $repo, $this->stripeService),
        ];
    }

    public function testPauseCallsStripeAndFreezesTheSubscription(): void
    {
        [$pause] = $this->useCases();
        $this->stripeService->expects($this->once())->method('pauseSubscription')->with('sub_123');

        $pause();

        self::assertTrue($this->subscription->isPaused());
        self::assertFalse($this->subscription->isValid());
    }

    public function testPauseIsIdempotentWhenAlreadyPaused(): void
    {
        [$pause] = $this->useCases(paused: true);
        $this->stripeService->expects($this->never())->method('pauseSubscription');

        $pause();
    }

    public function testResumeCallsStripeAndUnfreezes(): void
    {
        [, $resume] = $this->useCases(paused: true);
        $this->stripeService->expects($this->once())->method('resumeSubscription')->with('sub_123');

        $resume();

        self::assertFalse($this->subscription->isPaused());
    }

    public function testPauseRejectsNonAdmin(): void
    {
        [$pause] = $this->useCases(isAdmin: false);
        $this->stripeService->expects($this->never())->method('pauseSubscription');

        $this->expectException(NotWorkspaceAdminException::class);
        $pause();
    }
}
