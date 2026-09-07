<?php

declare(strict_types=1);

namespace App\Tests\Application\Dashboard;

use App\Application\Dashboard\UseCase\GetUserDashboardStatsUseCase;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Firm\Entity\RegulatoryProfile;
use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\WorkspaceType;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Tests\Application\ReflectionHelperTrait;
use Pagerfanta\Pagerfanta;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class GetUserDashboardStatsUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    public function testAggregatesCountersAndChecklistState(): void
    {
        $workspace = $this->createEntityState(Workspace::class, [
            'id' => Uuid::v7(),
            'name' => 'Cabinet Durand',
            'type' => WorkspaceType::FIRM,
            'trialDossiersRemaining' => 3,
            'meetingMinutesAllocated' => 90,
            'meetingSecondsConsumed' => 30 * 60,
            'subscription' => null,
            'siret' => '12345678900011',
            'siren' => '123456789',
        ]);

        $profile = $this->createEntityState(RegulatoryProfile::class, [
            'oriasNumber' => '12000001',
            'professionalAssociation' => 'ANACOFI',
            'rcProInsurer' => 'MMA',
            'rcProPolicyNumber' => 'POL-1',
        ]);

        $user = $this->createEntityState(User::class, [
            'googleAuthenticatorSecret' => 'SECRET',
            'isTotpVerified' => true,
        ]);

        $folderRepo = $this->createStub(ComplianceFolderRepositoryInterface::class);
        $folderRepo->method('countActiveForWorkspace')->willReturn(4);
        $folderRepo->method('countDraftsForWorkspace')->willReturn(2);
        $folderRepo->method('countForWorkspace')->willReturn(6);

        $clientsPage = $this->createStub(Pagerfanta::class);
        $clientsPage->method('getNbResults')->willReturn(7);
        $clientRepo = $this->createStub(ClientRepositoryInterface::class);
        $clientRepo->method('findAllByWorkspace')->willReturn($clientsPage);

        $memberRepo = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $memberRepo->method('findByWorkspace')->willReturn([1, 2, 3]);

        $profileRepo = $this->createStub(RegulatoryProfileRepositoryInterface::class);
        $profileRepo->method('findOneByWorkspace')->willReturn($profile);

        $workspaceProvider = $this->createStub(CurrentWorkspaceProvider::class);
        $workspaceProvider->method('getWorkspace')->willReturn($workspace);
        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn($user);

        $useCase = new GetUserDashboardStatsUseCase(
            $folderRepo,
            $clientRepo,
            $memberRepo,
            $profileRepo,
            $workspaceProvider,
            $userProvider,
        );

        $stats = $useCase();

        self::assertSame('Cabinet Durand', $stats->workspaceName);
        self::assertTrue($stats->isFirm);
        self::assertFalse($stats->hasActiveSubscription);
        self::assertSame(3, $stats->trialDossiersRemaining);
        self::assertSame(60, $stats->remainingMeetingMinutes);
        self::assertSame(4, $stats->activeFoldersCount);
        self::assertSame(2, $stats->draftFoldersCount);
        self::assertSame(6, $stats->totalFoldersCount);
        self::assertSame(7, $stats->clientsCount);
        self::assertSame(3, $stats->teamMembersCount);
        self::assertTrue($stats->isOrgCompleted);
        self::assertTrue($stats->isRegProfileValid);
        self::assertTrue($stats->is2faEnabled);
    }

    public function testHandlesMissingRegulatoryProfile(): void
    {
        $workspace = $this->createEntityState(Workspace::class, [
            'id' => Uuid::v7(),
            'name' => 'Solo',
            'type' => WorkspaceType::INDIVIDUAL,
            'trialDossiersRemaining' => 5,
            'meetingMinutesAllocated' => 0,
            'meetingSecondsConsumed' => 0,
            'subscription' => null,
            'siret' => null,
            'siren' => null,
        ]);

        $user = $this->createEntityState(User::class, []);

        $folderRepo = $this->createStub(ComplianceFolderRepositoryInterface::class);
        $clientsPage = $this->createStub(Pagerfanta::class);
        $clientsPage->method('getNbResults')->willReturn(0);
        $clientRepo = $this->createStub(ClientRepositoryInterface::class);
        $clientRepo->method('findAllByWorkspace')->willReturn($clientsPage);
        $memberRepo = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $memberRepo->method('findByWorkspace')->willReturn([1]);
        $profileRepo = $this->createStub(RegulatoryProfileRepositoryInterface::class);
        $profileRepo->method('findOneByWorkspace')->willReturn(null);
        $workspaceProvider = $this->createStub(CurrentWorkspaceProvider::class);
        $workspaceProvider->method('getWorkspace')->willReturn($workspace);
        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn($user);

        $stats = (new GetUserDashboardStatsUseCase(
            $folderRepo,
            $clientRepo,
            $memberRepo,
            $profileRepo,
            $workspaceProvider,
            $userProvider,
        ))();

        self::assertFalse($stats->isRegProfileValid);
        self::assertFalse($stats->isOrgCompleted);
        self::assertFalse($stats->is2faEnabled);
        self::assertSame(5, $stats->onboardingTotalSteps());
    }
}
