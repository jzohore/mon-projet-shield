<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\Client;

use App\Application\Compliance\UseCase\Client\RemoveClientFromWorkspaceUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Event\ClientDetachedFromWorkspaceEvent;
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

final class RemoveClientFromWorkspaceUseCaseTest extends TestCase
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

    public function testDetachesTheClientDeletesEmptyDraftsAndDispatches(): void
    {
        $folder = $this->folder();
        $client = $this->client($folder);
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn($client);

        $this->folderRepository->expects($this->once())->method('save')->with($folder, false);
        $this->clientRepository->expects($this->once())->method('save')->with($client);

        $captured = null;
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$captured): object {
                $captured = $event;

                return $event;
            });

        ($this->useCase())('cli_1');

        self::assertSame(ComplianceFolderStatus::DELETED, $folder->status);
        self::assertFalse($client->workspaces->contains($this->workspace));
        self::assertInstanceOf(ClientDetachedFromWorkspaceEvent::class, $captured);
        self::assertSame(['comp_fol_1'], $captured->deletedDraftSlugIds);
    }

    public function testDetachesAClientWithNoFolderAtAll(): void
    {
        $client = $this->client();
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn($client);

        $this->folderRepository->expects($this->never())->method('save');
        $this->clientRepository->expects($this->once())->method('save')->with($client);
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->with($this->isInstanceOf(ClientDetachedFromWorkspaceEvent::class));

        ($this->useCase())('cli_1');

        self::assertFalse($client->workspaces->contains($this->workspace));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRefusesANonAdmin(): void
    {
        $this->isAdmin = false;

        $this->clientRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        ($this->useCase())('cli_1');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenClientNotFoundInWorkspace(): void
    {
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn(null);

        $this->expectException(ClientNotFoundException::class);
        ($this->useCase())('cli_unknown');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRefusesWhenAFolderCarriesEvidence(): void
    {
        $engaged = $this->folder(['status' => ComplianceFolderStatus::APPROVED]);
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn($this->client($engaged));

        $this->clientRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        ($this->useCase())('cli_1');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRefusesWhenAFolderIsUnderLegalHold(): void
    {
        $held = $this->folder(['isUnderLegalHold' => true]);
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn($this->client($held));

        $this->clientRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        ($this->useCase())('cli_1');
    }
}
