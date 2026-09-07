<?php

declare(strict_types=1);

namespace App\Tests\Domain\Workspace\Entity;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Enum\SubscriptionStatus;
use App\Domain\Workspace\Entity\Workspace;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\TestCase;

final class WorkspaceQuotaTest extends TestCase
{
    use ReflectionHelperTrait;

    private function workspace(
        int $trialDossiersRemaining = 5,
        int $meetingMinutesAllocated = 90,
        int $meetingSecondsConsumed = 0,
        ?SubscriptionStatus $subscriptionStatus = null,
    ): Workspace {
        $subscription = $subscriptionStatus instanceof SubscriptionStatus
            ? $this->createEntityState(Subscription::class, ['status' => $subscriptionStatus])
            : null;

        return $this->createEntityState(Workspace::class, [
            'trialDossiersRemaining' => $trialDossiersRemaining,
            'meetingMinutesAllocated' => $meetingMinutesAllocated,
            'meetingSecondsConsumed' => $meetingSecondsConsumed,
            'subscription' => $subscription,
        ]);
    }

    public function testRemainingMeetingMinutesFloorsAtZero(): void
    {
        $workspace = $this->workspace(meetingMinutesAllocated: 90, meetingSecondsConsumed: 90 * 60 + 30);

        self::assertSame(0, $workspace->remainingMeetingMinutes());
    }

    public function testRemainingMeetingMinutesUsesFullConsumedMinutes(): void
    {
        // 25 min 40 s consommées => 25 min pleines décomptées.
        $workspace = $this->workspace(meetingMinutesAllocated: 90, meetingSecondsConsumed: 25 * 60 + 40);

        self::assertSame(65, $workspace->remainingMeetingMinutes());
    }

    public function testTrialWorkspaceCanOpenFolderWhileCounterPositive(): void
    {
        $workspace = $this->workspace(trialDossiersRemaining: 1);

        self::assertTrue($workspace->canOpenComplianceFolder());
        $workspace->consumeTrialComplianceFolder();

        self::assertSame(0, $workspace->trialDossiersRemaining);
        self::assertFalse($workspace->canOpenComplianceFolder());
    }

    public function testConsumingAnExhaustedTrialThrows(): void
    {
        $workspace = $this->workspace(trialDossiersRemaining: 0);

        $this->expectException(\DomainException::class);
        $workspace->consumeTrialComplianceFolder();
    }

    public function testActiveSubscriptionGivesUnlimitedFoldersAndNeverDecrementsTrial(): void
    {
        $workspace = $this->workspace(trialDossiersRemaining: 0, subscriptionStatus: SubscriptionStatus::ACTIVE);

        self::assertTrue($workspace->hasActiveSubscription());
        self::assertTrue($workspace->canOpenComplianceFolder());

        $workspace->consumeTrialComplianceFolder();

        self::assertSame(0, $workspace->trialDossiersRemaining);
    }

    public function testPastDueSubscriptionIsNotActive(): void
    {
        $workspace = $this->workspace(trialDossiersRemaining: 0, subscriptionStatus: SubscriptionStatus::PAST_DUE);

        self::assertFalse($workspace->hasActiveSubscription());
        self::assertFalse($workspace->canOpenComplianceFolder());
    }

    public function testGrantTrialComplianceFoldersIsAdditive(): void
    {
        $workspace = $this->workspace(trialDossiersRemaining: 2);

        $workspace->grantTrialComplianceFolders(3);

        self::assertSame(5, $workspace->trialDossiersRemaining);
    }

    public function testGrantTrialComplianceFoldersRejectsNonPositive(): void
    {
        $workspace = $this->workspace();

        $this->expectException(\DomainException::class);
        $workspace->grantTrialComplianceFolders(0);
    }

    public function testGrantMeetingMinutesIsAdditive(): void
    {
        $workspace = $this->workspace(meetingMinutesAllocated: 90);

        $workspace->grantMeetingMinutes(300);

        self::assertSame(390, $workspace->meetingMinutesAllocated);
        self::assertSame(390, $workspace->remainingMeetingMinutes());
    }

    public function testGrantMeetingMinutesRejectsNonPositive(): void
    {
        $workspace = $this->workspace();

        $this->expectException(\DomainException::class);
        $workspace->grantMeetingMinutes(-10);
    }
}
