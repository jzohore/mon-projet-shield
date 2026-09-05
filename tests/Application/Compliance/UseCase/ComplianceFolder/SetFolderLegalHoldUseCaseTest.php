<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\ComplianceFolder;

use App\Application\Compliance\UseCase\ComplianceFolder\SetFolderLegalHoldUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Event\FolderLegalHoldChangedEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class SetFolderLegalHoldUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private SetFolderLegalHoldUseCase $useCase;

    protected function setUp(): void
    {
        $this->folderRepository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $workspaceMemberRepository = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $workspaceMemberRepository->method('isUserAdminOfWorkspace')->willReturn(true);

        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn(
            $this->createEntityState(User::class, ['slugId' => 'usr_1', 'firstName' => 'Marie', 'lastName' => 'Curie'])
        );

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $transactionManager->method('transactional')->willReturnCallback(
            static fn (callable $operation): mixed => $operation()
        );

        $this->useCase = new SetFolderLegalHoldUseCase(
            $this->folderRepository,
            $workspaceMemberRepository,
            $userProvider,
            $transactionManager,
            $this->eventDispatcher,
        );
    }

    private function folder(bool $underHold = false): BusinessFolder
    {
        return $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'reference' => 'DOS-1',
            'status' => ComplianceFolderStatus::APPROVED,
            'history' => [],
            'isConfidential' => false,
            'isUnderLegalHold' => $underHold,
            'workspace' => $this->createEntityState(Workspace::class, ['slugId' => 'wrk_1', 'name' => 'Cabinet']),
        ]);
    }

    public function testPlacesAHold(): void
    {
        $folder = $this->folder();

        $this->folderRepository->expects($this->once())->method('save');
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->with($this->callback(static fn (FolderLegalHoldChangedEvent $e): bool => $e->placed));

        ($this->useCase)($folder, place: true, reason: 'Signalement Tracfin');

        self::assertTrue($folder->isUnderLegalHold);
    }

    public function testLiftsAHold(): void
    {
        $folder = $this->folder(underHold: true);

        $this->folderRepository->expects($this->once())->method('save');
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->with($this->callback(static fn (FolderLegalHoldChangedEvent $e): bool => !$e->placed));

        ($this->useCase)($folder, place: false);

        self::assertFalse($folder->isUnderLegalHold);
    }
}
