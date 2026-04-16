<?php

declare(strict_types=1);

namespace App\Tests\Application\Screening\UseCase;

use App\Application\Screening\DTO\Request\ScreeningRequest;
use App\Application\Screening\UseCase\PerformScreeningUseCase;
use App\Domain\Billing\Exception\NotEnoughCreditsException;
use App\Domain\Port\OpenSanctionsClientInterface;
use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Screening\Enum\ScreeningStatus;
use App\Domain\Screening\Event\ScreeningCompletedEvent;
use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\Industry;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class PerformScreeningUseCaseTest extends TestCase
{
    #[Test]
    public function it_creates_a_screening_audit_and_dispatches_event_when_no_cached_audit_exists(): void
    {
        $auditRepository = $this->createMock(ScreeningAuditRepositoryInterface::class);
        $openSanctionsClient = $this->createMock(OpenSanctionsClientInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $workspaceRepository = $this->createMock(WorkspaceRepositoryInterface::class);
        $logger = $this->createStub(LoggerInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $workspace = $this->createWorkspace(balance: 2);
        $user = $this->createUser();

        $request = new ScreeningRequest();
        $request->nameToSearch = 'John Doe';
        $request->schemaToSearch = 'Person';
        $request->workspaceSlugId = $workspace->slugId;
        $request->chargeCredit = true;
        $request->userEmail = $user->getUserIdentifier();

        $apiResult = [
            'alerts' => [
                ['name' => 'John Doe', 'score' => 95],
            ],
            'total_matches' => 1,
        ];

        $workspaceRepository->expects(self::once())
            ->method('findOneBySlug')
            ->with($workspace->slugId)
            ->willReturn($workspace);

        $userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($request->userEmail)
            ->willReturn($user);

        $auditRepository->expects(self::once())
            ->method('findRecentIdenticalSearch')
            ->with($workspace, $request->nameToSearch, 24)
            ->willReturn(null);

        $openSanctionsClient->expects(self::once())
            ->method('search')
            ->with($request->nameToSearch, $request->schemaToSearch)
            ->willReturn($apiResult);

        $auditRepository->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(ScreeningAudit::class));

        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $event): bool {
                return $event instanceof ScreeningCompletedEvent
                    && $event->query === 'John Doe'
                    && $event->workspaceSlugId !== ''
                    && $event->userEmail !== ''
                    && $event->cost === 1;
            }));

        $useCase = new PerformScreeningUseCase(
            $auditRepository,
            $openSanctionsClient,
            $eventDispatcher,
            $workspaceRepository,
            $logger,
            $userRepository,
        );

        $response = $useCase($request);

        self::assertSame('John Doe', $response->query);
        self::assertSame(1, $response->totalMatches);
        self::assertSame(ScreeningStatus::WAIT->value, $response->status);
        self::assertSame($apiResult['alerts'], $response->results);
    }

    #[Test]
    public function it_returns_the_cached_audit_when_a_recent_identical_search_exists(): void
    {
        $auditRepository = $this->createMock(ScreeningAuditRepositoryInterface::class);
        $openSanctionsClient = $this->createMock(OpenSanctionsClientInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $workspaceRepository = $this->createMock(WorkspaceRepositoryInterface::class);
        $logger = $this->createStub(LoggerInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $workspace = $this->createWorkspace(balance: 2);
        $user = $this->createUser();
        $cachedAudit = $this->createScreeningAudit($workspace, $user);

        $request = new ScreeningRequest();
        $request->nameToSearch = 'John Doe';
        $request->schemaToSearch = 'Person';
        $request->workspaceSlugId = $workspace->slugId;
        $request->chargeCredit = true;
        $request->userEmail = $user->getUserIdentifier();

        $workspaceRepository->expects(self::once())
            ->method('findOneBySlug')
            ->with($workspace->slugId)
            ->willReturn($workspace);

        $userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($request->userEmail)
            ->willReturn($user);

        $auditRepository->expects(self::once())
            ->method('findRecentIdenticalSearch')
            ->with($workspace, $request->nameToSearch, 24)
            ->willReturn($cachedAudit);

        $openSanctionsClient->expects(self::never())
            ->method('search');

        $auditRepository->expects(self::never())
            ->method('save');

        $eventDispatcher->expects(self::never())
            ->method('dispatch');

        $useCase = new PerformScreeningUseCase(
            $auditRepository,
            $openSanctionsClient,
            $eventDispatcher,
            $workspaceRepository,
            $logger,
            $userRepository,
        );

        $response = $useCase($request);

        self::assertTrue($response->isCached);
        self::assertSame($cachedAudit->slugId, $response->slugId);
        self::assertSame($cachedAudit->query, $response->query);
        self::assertSame($cachedAudit->totalMatches, $response->totalMatches);
        self::assertSame($cachedAudit->results, $response->results);
    }

    #[Test]
    public function it_throws_when_charge_credit_is_requested_but_workspace_has_not_enough_credits(): void
    {
        $auditRepository = $this->createMock(ScreeningAuditRepositoryInterface::class);
        $openSanctionsClient = $this->createMock(OpenSanctionsClientInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $workspaceRepository = $this->createMock(WorkspaceRepositoryInterface::class);
        $logger = $this->createStub(LoggerInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $workspace = $this->createWorkspace(balance: 0);
        $user = $this->createUser();

        $request = new ScreeningRequest();
        $request->nameToSearch = 'John Doe';
        $request->schemaToSearch = 'Person';
        $request->workspaceSlugId = $workspace->slugId;
        $request->chargeCredit = true;
        $request->userEmail = $user->getUserIdentifier();

        $workspaceRepository->expects(self::once())
            ->method('findOneBySlug')
            ->with($workspace->slugId)
            ->willReturn($workspace);

        $userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($request->userEmail)
            ->willReturn($user);

        $auditRepository->expects(self::once())
            ->method('findRecentIdenticalSearch')
            ->with($workspace, $request->nameToSearch, 24)
            ->willReturn(null);

        $openSanctionsClient->expects(self::never())
            ->method('search');

        $auditRepository->expects(self::never())
            ->method('save');

        $eventDispatcher->expects(self::never())
            ->method('dispatch');

        $useCase = new PerformScreeningUseCase(
            $auditRepository,
            $openSanctionsClient,
            $eventDispatcher,
            $workspaceRepository,
            $logger,
            $userRepository,
        );

        $this->expectException(NotEnoughCreditsException::class);

        $useCase($request);
    }

    private function createWorkspace(int $balance): Workspace
    {
        $workspace = Workspace::create(
            name: 'Cabinet Test',
            siret: '12345678901234',
            legalName: 'Cabinet Test SARL',
            address: '1 rue de Paris',
            industry: Industry::LAWYER
        );

        $reflection = new \ReflectionObject($workspace);
        $property = $reflection->getProperty('balance');
        $property->setAccessible(true);
        $property->setValue($workspace, $balance);

        return $workspace;
    }

    private function createUser(): User
    {
        return User::create(
            email: 'user@example.com',
            firstName: 'John',
            lastName: 'Doe',
            isVerified: true,
            roles: ['ROLE_USER'],
            isActif: true,
        );
    }

    private function createScreeningAudit(Workspace $workspace, User $user): ScreeningAudit
    {
        return ScreeningAudit::create(
            workspace: $workspace,
            ower: $user,
            query: 'John Doe',
            results: [
                ['name' => 'John Doe', 'score' => 95],
            ],
            totalMatches: 1,
        );
    }
}
