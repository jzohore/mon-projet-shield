<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\Client;

use App\Application\Compliance\UseCase\Client\RemoveClientFromWorkspaceUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Event\ClientAccountDeletedEvent;
use App\Domain\Compliance\Event\ClientDetachedFromWorkspaceEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\Client;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\ClientRemovalReason;
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

final class RemoveClientFromWorkspaceUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private ClientRepositoryInterface&MockObject $clientRepository;
    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private Workspace $workspace;
    private Workspace $otherWorkspace;
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
        $this->otherWorkspace = $this->createEntityState(Workspace::class, [
            'slugId' => 'wrk_2',
            'name' => 'Autre cabinet',
            'clients' => new ArrayCollection(),
        ]);
    }

    private function useCase(): RemoveClientFromWorkspaceUseCase
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

        return new RemoveClientFromWorkspaceUseCase(
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
    private function folder(array $overrides = []): BusinessFolder
    {
        return $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'reference' => 'DOS-1',
            'status' => ComplianceFolderStatus::DRAFT,
            'workspace' => $this->workspace,
            'isConfidential' => false,
            'isUnderLegalHold' => false,
            'submittedAt' => null,
            'relationshipEndedAt' => null,
            'documents' => new ArrayCollection(),
            'meetingRecordings' => new ArrayCollection(),
            ...$overrides,
        ]);
    }

    /**
     * @param list<Workspace>      $workspaces
     * @param list<BusinessFolder> $folders
     */
    private function client(array $workspaces, array $folders = []): Client
    {
        $client = $this->createEntityState(Client::class, [
            'slugId' => 'cli_1',
            'email' => 'jean@example.com',
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'isActif' => false,
            'createdAt' => new \DateTimeImmutable('2025-01-01'),
            'workspaces' => new ArrayCollection(),
            'relations' => new ArrayCollection(),
            'complianceFolders' => new ArrayCollection($folders),
        ]);
        foreach ($workspaces as $ws) {
            $client->attachToWorkspace($ws);
        }

        return $client;
    }

    public function testDeletesTheAccountWhenClientHasNoFolderAndOnlyThisWorkspace(): void
    {
        $client = $this->client([$this->workspace]);
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn($client);

        $this->clientRepository->expects($this->once())->method('remove')->with($client);
        $this->clientRepository->expects($this->never())->method('save');
        $this->folderRepository->expects($this->never())->method('save');

        $captured = null;
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$captured): object {
                $captured = $event;

                return $event;
            });

        ($this->useCase())('cli_1', ClientRemovalReason::CREATION_ERRONEE);

        self::assertInstanceOf(ClientAccountDeletedEvent::class, $captured);
        self::assertSame('jean@example.com', $captured->clientEmail);
        self::assertSame(ClientRemovalReason::CREATION_ERRONEE, $captured->reason);
        self::assertSame(0, $captured->evidenceCheck['folders_count']);
    }

    public function testDetachesWhenClientBelongsToAnotherWorkspace(): void
    {
        $client = $this->client([$this->workspace, $this->otherWorkspace]);
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn($client);

        $this->clientRepository->expects($this->never())->method('remove');
        $this->clientRepository->expects($this->once())->method('save')->with($client);
        $this->folderRepository->expects($this->never())->method('save');

        $captured = null;
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$captured): object {
                $captured = $event;

                return $event;
            });

        ($this->useCase())('cli_1', ClientRemovalReason::FIN_COLLABORATION);

        self::assertInstanceOf(ClientDetachedFromWorkspaceEvent::class, $captured);
        self::assertTrue($captured->wasMultiWorkspace);
        self::assertSame([], $captured->deletedDraftSlugIds);
        self::assertFalse($client->workspaces->contains($this->workspace));
    }

    public function testDetachesAndDeletesEmptyDraftsWhenClientHasADraftHere(): void
    {
        $draft = $this->folder();
        $client = $this->client([$this->workspace], [$draft]);
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn($client);

        $this->clientRepository->expects($this->never())->method('remove');
        $this->folderRepository->expects($this->once())->method('save')->with($draft, false);
        $this->clientRepository->expects($this->once())->method('save')->with($client);

        $captured = null;
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$captured): object {
                $captured = $event;

                return $event;
            });

        ($this->useCase())('cli_1', ClientRemovalReason::JAMAIS_ENTRE_EN_RELATION);

        self::assertInstanceOf(ClientDetachedFromWorkspaceEvent::class, $captured);
        self::assertFalse($captured->wasMultiWorkspace);
        self::assertSame(['comp_fol_1'], $captured->deletedDraftSlugIds);
        self::assertSame(ComplianceFolderStatus::DELETED, $draft->status);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testReturnsSilentlyWhenClientAlreadyGone(): void
    {
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn(null);

        $this->clientRepository->expects($this->never())->method('remove');
        $this->clientRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        ($this->useCase())('cli_unknown', ClientRemovalReason::AUTRE);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRefusesANonAdmin(): void
    {
        $this->isAdmin = false;

        $this->clientRepository->expects($this->never())->method('remove');
        $this->clientRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        ($this->useCase())('cli_1', ClientRemovalReason::AUTRE);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRefusesWhenAFolderHereCarriesEvidence(): void
    {
        $engaged = $this->folder(['status' => ComplianceFolderStatus::APPROVED]);
        $client = $this->client([$this->workspace, $this->otherWorkspace], [$engaged]);
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn($client);

        $this->clientRepository->expects($this->never())->method('remove');
        $this->clientRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        ($this->useCase())('cli_1', ClientRemovalReason::AUTRE);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRefusesWhenAFolderHereIsUnderLegalHold(): void
    {
        $held = $this->folder(['isUnderLegalHold' => true]);
        $client = $this->client([$this->workspace, $this->otherWorkspace], [$held]);
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn($client);

        $this->clientRepository->expects($this->never())->method('remove');
        $this->clientRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        ($this->useCase())('cli_1', ClientRemovalReason::AUTRE);
    }
}
