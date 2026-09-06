<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\Client;

use App\Application\Compliance\UseCase\Client\EndClientRelationshipUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Enum\RelationshipEndReason;
use App\Domain\Compliance\Event\BusinessRelationshipEndedEvent;
use App\Domain\Compliance\Event\ClientRelationshipEndedEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\Client;
use App\Domain\User\Entity\User;
use App\Domain\User\Exception\ClientNotFoundException;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EndClientRelationshipUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private ClientRepositoryInterface&MockObject $clientRepository;
    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private Workspace $workspace;
    private bool $isAdmin = true;

    protected function setUp(): void
    {
        $this->clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $this->folderRepository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->workspace = $this->createEntityState(Workspace::class, [
            'slugId' => 'wrk_1',
            'name' => 'Cabinet',
            'clients' => new ArrayCollection(),
        ]);
    }

    private function useCase(): EndClientRelationshipUseCase
    {
        $workspaceMemberRepository = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $workspaceMemberRepository->method('isUserAdminOfWorkspace')->willReturn($this->isAdmin);

        $workspaceProvider = $this->createStub(CurrentWorkspaceProvider::class);
        $workspaceProvider->method('getWorkspace')->willReturn($this->workspace);

        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn(
            $this->createEntityState(User::class, ['slugId' => 'usr_1', 'firstName' => 'Marie', 'lastName' => 'Curie'])
        );

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $transactionManager->method('transactional')->willReturnCallback(
            static fn (callable $operation): mixed => $operation()
        );

        return new EndClientRelationshipUseCase(
            $this->clientRepository,
            $this->folderRepository,
            $workspaceMemberRepository,
            $workspaceProvider,
            $userProvider,
            $transactionManager,
            $this->eventDispatcher,
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function folder(string $slugId, array $overrides = []): BusinessFolder
    {
        return $this->createEntityState(BusinessFolder::class, [
            'slugId' => $slugId,
            'reference' => 'DOS-' . $slugId,
            'status' => ComplianceFolderStatus::DRAFT,
            'workspace' => $this->workspace,
            'isConfidential' => false,
            'isUnderLegalHold' => false,
            'submittedAt' => null,
            'relationshipEndedAt' => null,
            'purgeDueAt' => null,
            'history' => [],
            'documents' => new ArrayCollection(),
            'meetingRecordings' => new ArrayCollection(),
            ...$overrides,
        ]);
    }

    private function client(BusinessFolder ...$folders): Client
    {
        return $this->createEntityState(Client::class, [
            'slugId' => 'cli_1',
            'email' => 'jean@example.com',
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'workspaces' => new ArrayCollection([$this->workspace]),
            'complianceFolders' => new ArrayCollection($folders),
        ]);
    }

    /**
     * @return list<object>
     */
    private function captureDispatched(): array
    {
        $events = [];
        $this->eventDispatcher->method('dispatch')->willReturnCallback(
            static function (object $event) use (&$events): object {
                $events[] = $event;

                return $event;
            }
        );

        return $events;
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testEndsEngagedFoldersDeletesEmptyDraftsAndDispatchesTheUmbrellaEvent(): void
    {
        $engaged = $this->folder('comp_fol_engaged', ['status' => ComplianceFolderStatus::APPROVED]);
        $emptyDraft = $this->folder('comp_fol_draft');
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')
            ->willReturn($this->client($engaged, $emptyDraft));

        $events = [];
        $this->eventDispatcher->expects($this->exactly(2))->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$events): object {
                $events[] = $event;

                return $event;
            });

        ($this->useCase())('cli_1', RelationshipEndReason::DEPART_CLIENT);

        self::assertNotNull($engaged->relationshipEndedAt);
        self::assertSame(ComplianceFolderStatus::DELETED, $emptyDraft->status);

        self::assertInstanceOf(BusinessRelationshipEndedEvent::class, $events[0]);
        self::assertSame('comp_fol_engaged', $events[0]->folderSlugId);
        self::assertSame('Départ du client', $events[0]->reason);

        self::assertInstanceOf(ClientRelationshipEndedEvent::class, $events[1]);
        self::assertSame(RelationshipEndReason::DEPART_CLIENT, $events[1]->reason);
        self::assertSame(['comp_fol_engaged'], $events[1]->endedFolderSlugIds);
        self::assertSame(['comp_fol_draft'], $events[1]->deletedDraftSlugIds);
        self::assertNotNull($events[1]->latestPurgeDueAt);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testOnlyEmptyDraftsMeansNoPerFolderEventButStillTheUmbrella(): void
    {
        $draftA = $this->folder('comp_fol_a');
        $draftB = $this->folder('comp_fol_b');
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')
            ->willReturn($this->client($draftA, $draftB));

        $captured = null;
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$captured): object {
                $captured = $event;

                return $event;
            });

        ($this->useCase())('cli_1', RelationshipEndReason::FIN_DE_MANDAT);

        self::assertInstanceOf(ClientRelationshipEndedEvent::class, $captured);
        self::assertSame([], $captured->endedFolderSlugIds);
        self::assertSame(['comp_fol_a', 'comp_fol_b'], $captured->deletedDraftSlugIds);
        self::assertNull($captured->latestPurgeDueAt);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testIsIdempotentWhenEveryFolderIsAlreadyEnded(): void
    {
        $already = $this->folder('comp_fol_done', [
            'status' => ComplianceFolderStatus::APPROVED,
            'relationshipEndedAt' => new \DateTimeImmutable('-1 year'),
        ]);
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn($this->client($already));

        $this->folderRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        ($this->useCase())('cli_1', RelationshipEndReason::DEPART_CLIENT);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRefusesANonAdmin(): void
    {
        $this->isAdmin = false;
        $this->captureDispatched();

        $this->folderRepository->expects($this->never())->method('save');

        $this->expectException(\DomainException::class);
        ($this->useCase())('cli_1', RelationshipEndReason::DEPART_CLIENT);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenClientNotFound(): void
    {
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn(null);

        $this->expectException(ClientNotFoundException::class);
        ($this->useCase())('cli_unknown', RelationshipEndReason::DEPART_CLIENT);
    }
}
