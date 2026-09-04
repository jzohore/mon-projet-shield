<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Application\Compliance\UseCase\ComplianceDocument\DER\RevokeDerAcknowledgementUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Event\DerAcknowledgementRevokedEvent;
use App\Domain\Compliance\Exception\DerAcknowledgementNotFoundException;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Repository\DerAcknowledgementRepositoryInterface;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class RevokeDerAcknowledgementUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private DerAcknowledgementRepositoryInterface&Stub $acknowledgementRepository;
    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private WorkspaceMemberRepositoryInterface&Stub $workspaceMemberRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private RevokeDerAcknowledgementUseCase $useCase;

    protected function setUp(): void
    {
        $this->acknowledgementRepository = $this->createStub(DerAcknowledgementRepositoryInterface::class);
        $this->folderRepository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->workspaceMemberRepository = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $this->workspaceMemberRepository->method('isUserAdminOfWorkspace')->willReturn(true);

        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn(
            $this->createEntityState(User::class, ['firstName' => 'Marie', 'lastName' => 'Curie'])
        );

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $transactionManager->method('transactional')->willReturnCallback(
            static fn (callable $operation): mixed => $operation()
        );

        $this->useCase = new RevokeDerAcknowledgementUseCase(
            $this->acknowledgementRepository,
            $this->folderRepository,
            $this->workspaceMemberRepository,
            $userProvider,
            $transactionManager,
            $this->eventDispatcher,
        );
    }

    private function acknowledgement(): DerAcknowledgement
    {
        $workspace = $this->createEntityState(Workspace::class, ['slugId' => 'wrk_1', 'name' => 'Cabinet']);
        $folder = $this->createEntityState(BusinessFolder::class, ['slugId' => 'comp_fol_1', 'workspace' => $workspace, 'history' => []]);
        $document = $this->createEntityState(ComplianceDocument::class, [
            'id' => Uuid::v7(),
            'folder' => $folder,
            'acknowledgements' => new ArrayCollection(),
        ]);

        return DerAcknowledgement::record(
            document: $document,
            pdfSha256: str_repeat('a', 64),
            pdfStoragePath: 'p.pdf',
            declaredName: 'Alice Martin',
            recipientEmail: 'client@acme.test',
            statement: DerStatement::current(),
        );
    }

    public function testRevokesTheAcknowledgementAndRejectsTheFolder(): void
    {
        $acknowledgement = $this->acknowledgement();
        $this->acknowledgementRepository->method('findBySlugId')->willReturn($acknowledgement);

        $this->folderRepository->expects($this->once())->method('save');
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (DerAcknowledgementRevokedEvent $e): bool => 'Destinataire erroné' === $e->getReason()
                && 'Marie CURIE' === $e->getRevokedByName()))
            ->willReturnArgument(0);

        ($this->useCase)('der_ack_1', '  Destinataire erroné  ');

        self::assertTrue($acknowledgement->isRevoked());
        self::assertSame(ComplianceFolderStatus::DER_REJECTED, $acknowledgement->document->folder->status);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenTheAcknowledgementIsUnknown(): void
    {
        $this->acknowledgementRepository->method('findBySlugId')->willReturn(null);

        $this->expectException(DerAcknowledgementNotFoundException::class);
        ($this->useCase)('der_ack_unknown', 'motif');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenTheReasonIsBlank(): void
    {
        $this->acknowledgementRepository->method('findBySlugId')->willReturn($this->acknowledgement());

        $this->expectException(\DomainException::class);
        ($this->useCase)('der_ack_1', '   ');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenTheUserIsNotAuthorized(): void
    {
        $this->workspaceMemberRepository = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $this->workspaceMemberRepository->method('isUserAdminOfWorkspace')->willReturn(false);

        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn(
            $this->createEntityState(User::class, ['firstName' => 'Marie', 'lastName' => 'Curie'])
        );

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $transactionManager->method('transactional')->willReturnCallback(
            static fn (callable $operation): mixed => $operation()
        );

        $this->useCase = new RevokeDerAcknowledgementUseCase(
            $this->acknowledgementRepository,
            $this->folderRepository,
            $this->workspaceMemberRepository,
            $userProvider,
            $transactionManager,
            $this->eventDispatcher,
        );

        $this->acknowledgementRepository->method('findBySlugId')->willReturn($this->acknowledgement());

        $this->folderRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        ($this->useCase)('der_ack_1', 'motif');
    }
}
