<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\ComplianceFolder;

use App\Application\Compliance\UseCase\ComplianceFolder\EndBusinessRelationshipUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Event\BusinessRelationshipEndedEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EndBusinessRelationshipUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private WorkspaceMemberRepositoryInterface&Stub $workspaceMemberRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private EndBusinessRelationshipUseCase $useCase;

    protected function setUp(): void
    {
        $this->folderRepository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->workspaceMemberRepository = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $this->workspaceMemberRepository->method('isUserAdminOfWorkspace')->willReturn(true);

        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn(
            $this->createEntityState(User::class, ['slugId' => 'usr_1', 'firstName' => 'Marie', 'lastName' => 'Curie'])
        );

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $transactionManager->method('transactional')->willReturnCallback(
            static fn (callable $operation): mixed => $operation()
        );

        $this->useCase = new EndBusinessRelationshipUseCase(
            $this->folderRepository,
            $this->workspaceMemberRepository,
            $userProvider,
            $transactionManager,
            $this->eventDispatcher,
        );
    }

    private function folder(): BusinessFolder
    {
        return $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'reference' => 'DOS-1',
            'status' => ComplianceFolderStatus::APPROVED,
            'history' => [],
            'isConfidential' => false,
            'isUnderLegalHold' => false,
            'workspace' => $this->createEntityState(Workspace::class, ['slugId' => 'wrk_1', 'name' => 'Cabinet']),
        ]);
    }

    public function testEndsTheRelationshipPersistsAndDispatches(): void
    {
        $folder = $this->folder();

        $this->folderRepository->expects($this->once())->method('save')->with($folder);
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->with($this->isInstanceOf(BusinessRelationshipEndedEvent::class));

        ($this->useCase)($folder, 'Départ du client');

        self::assertNotNull($folder->relationshipEndedAt);
        self::assertNotNull($folder->purgeDueAt);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRefusesANonAdmin(): void
    {
        $workspaceMemberRepository = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $workspaceMemberRepository->method('isUserAdminOfWorkspace')->willReturn(false);

        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn(
            $this->createEntityState(User::class, ['slugId' => 'usr_1', 'firstName' => 'Jean', 'lastName' => 'Dupont'])
        );

        $transactionManager = $this->createStub(TransactionManagerInterface::class);

        $useCase = new EndBusinessRelationshipUseCase(
            $this->folderRepository,
            $workspaceMemberRepository,
            $userProvider,
            $transactionManager,
            $this->eventDispatcher,
        );

        $this->folderRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        $useCase($this->folder(), 'motif');
    }
}
