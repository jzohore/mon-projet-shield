<?php

declare(strict_types=1);

namespace App\Tests\Domain\Workspace\Service;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Enum\SubscriptionStatus;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\WorkspaceType;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\SeatAvailability;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\TestCase;

final class SeatAvailabilityTest extends TestCase
{
    use ReflectionHelperTrait;

    private function service(int $members, int $pending): SeatAvailability
    {
        $memberRepo = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $memberRepo->method('countByWorkspace')->willReturn($members);

        $invitationRepo = $this->createStub(WorkspaceInvitationRepositoryInterface::class);
        $invitationRepo->method('countPendingByWorkspace')->willReturn($pending);

        return new SeatAvailability($memberRepo, $invitationRepo);
    }

    private function workspace(
        WorkspaceType $type,
        ?SubscriptionStatus $status = null,
        int $seats = 1,
        string $planReference = 'cabinet',
    ): Workspace {
        $subscription = $status instanceof SubscriptionStatus ? $this->createEntityState(Subscription::class, [
            'status' => $status,
            'seatsCount' => $seats,
            'planReference' => $planReference,
            'currentPeriodEnd' => new \DateTimeImmutable('+20 days'),
        ]) : null;

        return $this->createEntityState(Workspace::class, ['type' => $type, 'subscription' => $subscription]);
    }

    public function testIndividualWorkspaceHasOneSeat(): void
    {
        self::assertSame(1, $this->service(1, 0)->allowedSeats($this->workspace(WorkspaceType::INDIVIDUAL)));
    }

    public function testTrialCabinetHasTwoSeats(): void
    {
        self::assertSame(2, $this->service(1, 0)->allowedSeats($this->workspace(WorkspaceType::FIRM)));
    }

    public function testSubscribedCabinetUsesBilledSeatCount(): void
    {
        $workspace = $this->workspace(WorkspaceType::FIRM, SubscriptionStatus::ACTIVE, seats: 6);

        self::assertSame(6, $this->service(3, 1)->allowedSeats($workspace));
    }

    public function testCabinetSubscriptionOpensSeatsEvenIfWorkspaceTypeStillIndividual(): void
    {
        // Cas réel : l'utilisateur a souscrit l'offre cabinet mais workspace.type
        // n'a pas (encore) été recalé.
        $workspace = $this->workspace(WorkspaceType::INDIVIDUAL, SubscriptionStatus::ACTIVE, seats: 3);

        self::assertSame(3, $this->service(1, 0)->allowedSeats($workspace));
    }

    public function testIndividualPlanSubscriptionStaysAtOneSeat(): void
    {
        $workspace = $this->workspace(WorkspaceType::INDIVIDUAL, SubscriptionStatus::ACTIVE, seats: 5, planReference: 'individual');

        self::assertSame(1, $this->service(1, 0)->allowedSeats($workspace));
    }

    public function testUsedSeatsSumsMembersAndPendingInvitations(): void
    {
        $service = $this->service(members: 3, pending: 2);

        self::assertSame(5, $service->usedSeats($this->workspace(WorkspaceType::FIRM)));
    }

    public function testHasFreeSeatWhenBelowLimit(): void
    {
        $workspace = $this->workspace(WorkspaceType::FIRM, SubscriptionStatus::ACTIVE, seats: 6);

        self::assertTrue($this->service(4, 1)->hasFreeSeat($workspace));   // 5 / 6
        self::assertFalse($this->service(5, 1)->hasFreeSeat($workspace));  // 6 / 6
        self::assertSame(0, $this->service(6, 1)->remainingSeats($workspace)); // plancher à 0
    }
}
