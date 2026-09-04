<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Application\Compliance\UseCase\ComplianceDocument\DER\GenerateDerUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Event\DerGenerationRequestedEvent;
use App\Domain\Compliance\Exception\Document\DocumentNotFoundException;
use App\Domain\Compliance\Exception\Document\InvalidDocumentFolderException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Kyc\Enum\DocumentStatus;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Infrastructure\Compliance\Message\GenerateDerPdfMessage;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class GenerateDerUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private ComplianceDocumentRepositoryInterface&MockObject $documentRepository;
    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private MessageBusInterface&MockObject $messageBus;
    private CurrentUserProvider&Stub $currentUserProvider;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private GenerateDerUseCase $useCase;

    protected function setUp(): void
    {
        $this->documentRepository = $this->createMock(ComplianceDocumentRepositoryInterface::class);
        $this->folderRepository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->currentUserProvider = $this->createStub(CurrentUserProvider::class);
        $this->currentUserProvider->method('isAuthenticated')->willReturn(false);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $transactionManager->method('transactional')->willReturnCallback(
            static fn (callable $operation): mixed => $operation()
        );

        $this->useCase = new GenerateDerUseCase(
            $this->documentRepository,
            $this->folderRepository,
            $this->messageBus,
            $this->currentUserProvider,
            $this->eventDispatcher,
            $transactionManager,
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function document(array $overrides = []): ComplianceDocument
    {
        $folder = $this->createEntityState(BusinessFolder::class, ['slugId' => 'comp_fol_1', 'history' => []]);

        return $this->createEntityState(ComplianceDocument::class, array_merge([
            'id' => Uuid::v7(),
            'folder' => $folder,
            'acknowledgements' => new ArrayCollection(),
        ], $overrides));
    }

    public function testGeneratesTheDerAndDispatchesThePdfMessageForAnAnonymousActor(): void
    {
        $document = $this->document(['derSendRequestedAt' => new \DateTimeImmutable('-1 day')]);
        $documentId = $document->id->toString();

        $this->documentRepository->method('findById')->willReturn($document);
        $this->documentRepository->expects($this->once())->method('save')->with($document);
        $this->folderRepository->expects($this->once())->method('save')->with($document->folder);

        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (GenerateDerPdfMessage $message): bool => $documentId === $message->documentId
                && null === $message->oldStoragePath))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        $this->eventDispatcher->expects($this->never())->method('dispatch');

        ($this->useCase)($documentId, $document->folder);

        self::assertSame(DocumentStatus::PENDING, $document->status);
        self::assertSame(ComplianceFolderStatus::DER_GENERATED, $document->folder->status);
        // 🔄 Nouveau cycle : le circuit d'accusé de réception a été réouvert.
        self::assertNull($document->derSendRequestedAt);
    }

    public function testDispatchesTheGenerationRequestedEventWhenTheActorIsAuthenticated(): void
    {
        $document = $this->document();
        $documentId = $document->id->toString();
        $user = $this->createEntityState(User::class, ['email' => 'cgp@cabinet.fr']);

        $this->currentUserProvider = $this->createStub(CurrentUserProvider::class);
        $this->currentUserProvider->method('isAuthenticated')->willReturn(true);
        $this->currentUserProvider->method('getUser')->willReturn($user);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $transactionManager->method('transactional')->willReturnCallback(
            static fn (callable $operation): mixed => $operation()
        );

        $this->useCase = new GenerateDerUseCase(
            $this->documentRepository,
            $this->folderRepository,
            $this->messageBus,
            $this->currentUserProvider,
            $this->eventDispatcher,
            $transactionManager,
        );

        $this->documentRepository->method('findById')->willReturn($document);
        $this->documentRepository->expects($this->once())->method('save')->with($document);
        $this->folderRepository->expects($this->once())->method('save')->with($document->folder);
        $this->messageBus->expects($this->once())->method('dispatch')->willReturnCallback(
            static fn (object $message): Envelope => new Envelope($message)
        );

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (DerGenerationRequestedEvent $e): bool => $document === $e->getDocument()
                && $user === $e->getUser()))
            ->willReturnArgument(0);

        ($this->useCase)($documentId, $document->folder);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenTheDocumentIsUnknown(): void
    {
        $folder = $this->createEntityState(BusinessFolder::class, ['slugId' => 'comp_fol_1', 'history' => []]);
        $this->documentRepository->method('findById')->willReturn(null);

        $this->folderRepository->expects($this->never())->method('save');
        $this->messageBus->expects($this->never())->method('dispatch');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(DocumentNotFoundException::class);
        ($this->useCase)('unknown-id', $folder);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenTheDocumentDoesNotBelongToTheFolder(): void
    {
        $document = $this->document();
        $otherFolder = $this->createEntityState(BusinessFolder::class, ['slugId' => 'comp_fol_2', 'history' => []]);
        $this->documentRepository->method('findById')->willReturn($document);

        $this->folderRepository->expects($this->never())->method('save');
        $this->messageBus->expects($this->never())->method('dispatch');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(InvalidDocumentFolderException::class);
        ($this->useCase)($document->id->toString(), $otherFolder);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testPropagatesTheSealedGuardWhenTheDerIsAlreadyAcknowledged(): void
    {
        $document = $this->document();
        $document->acknowledgements->add(DerAcknowledgement::record(
            document: $document,
            pdfSha256: str_repeat('a', 64),
            pdfStoragePath: 'p.pdf',
            declaredName: 'Alice',
            recipientEmail: 'a@b.test',
            statement: DerStatement::current(),
        ));
        $this->documentRepository->method('findById')->willReturn($document);

        $this->folderRepository->expects($this->never())->method('save');
        $this->messageBus->expects($this->never())->method('dispatch');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        ($this->useCase)($document->id->toString(), $document->folder);
    }
}
