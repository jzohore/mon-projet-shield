<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Application\Compliance\UseCase\ComplianceDocument\DER\SendDerToClientUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Event\DerPdfGeneratedEvent;
use App\Domain\Compliance\Event\DerSentEvent;
use App\Domain\Compliance\Exception\DerCannotBeSentException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Kyc\Enum\DocumentStatus;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class SendDerToClientUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private EventDispatcherInterface&MockObject $eventDispatcher;
    private CurrentUserProvider&Stub $currentUserProvider;
    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private ComplianceDocumentRepositoryInterface&Stub $documentRepository;
    private SendDerToClientUseCase $useCase;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->currentUserProvider = $this->createStub(CurrentUserProvider::class);
        $this->currentUserProvider->method('isAuthenticated')->willReturn(false);
        $this->folderRepository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->documentRepository = $this->createStub(ComplianceDocumentRepositoryInterface::class);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $transactionManager->method('transactional')->willReturnCallback(
            static fn (callable $operation): mixed => $operation()
        );

        $this->useCase = new SendDerToClientUseCase(
            $this->eventDispatcher,
            $this->currentUserProvider,
            $this->folderRepository,
            $this->documentRepository,
            $transactionManager,
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function folderWithDer(array $overrides = []): BusinessFolder
    {
        $folder = $this->createEntityState(BusinessFolder::class, [
            'id' => Uuid::v7(),
            'slugId' => 'comp_fol_1',
            'history' => [],
        ]);

        $document = $this->createEntityState(ComplianceDocument::class, array_merge([
            'id' => Uuid::v7(),
            'slugId' => 'comp_doc_1',
            'folder' => $folder,
            'status' => DocumentStatus::PENDING,
            'acknowledgements' => new ArrayCollection(),
        ], $overrides));

        $this->documentRepository->method('findDerByFolder')->willReturn($document);

        return $folder;
    }

    public function testRecordsTheSendRequestAndDispatchesDerSent(): void
    {
        $folder = $this->folderWithDer();
        $this->folderRepository->expects($this->once())->method('save')->with($folder);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (DerSentEvent $event): bool => 'SYSTEM_FLASH_ONBOARDING' === $event->getTriggeredByUserId()))
            ->willReturnArgument(0);

        ($this->useCase)($folder);

        self::assertSame(ComplianceFolderStatus::DER_SENT, $folder->status);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testDispatchesDerPdfGeneratedWhenThePdfIsAlreadyReady(): void
    {
        $folder = $this->folderWithDer(['status' => DocumentStatus::GENERATED]);
        $this->folderRepository->expects($this->once())->method('save');

        $dispatched = [];
        $this->eventDispatcher->method('dispatch')->willReturnCallback(
            static function (object $event) use (&$dispatched): object {
                $dispatched[] = $event::class;

                return $event;
            }
        );

        ($this->useCase)($folder);

        self::assertContains(DerSentEvent::class, $dispatched);
        self::assertContains(DerPdfGeneratedEvent::class, $dispatched);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRefusesWhenTheDerHasAlreadyBeenAcknowledged(): void
    {
        $document = $this->createEntityState(ComplianceDocument::class, [
            'id' => Uuid::v7(),
            'folder' => $this->createEntityState(BusinessFolder::class, ['id' => Uuid::v7(), 'slugId' => 'comp_fol_1', 'history' => []]),
            'acknowledgements' => new ArrayCollection(),
        ]);
        $document->acknowledgements->add(DerAcknowledgement::record(
            document: $document,
            pdfSha256: str_repeat('a', 64),
            pdfStoragePath: 'p.pdf',
            declaredName: 'Alice',
            recipientEmail: 'a@b.test',
            statement: DerStatement::current(),
        ));
        $this->documentRepository->method('findDerByFolder')->willReturn($document);

        $this->folderRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(DerCannotBeSentException::class);
        ($this->useCase)($document->folder);
    }
}
