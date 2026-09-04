<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Compliance\Handler;

use App\Application\Compliance\DTO\Response\ComplianceFolderShowResponse;
use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Event\DerPdfGeneratedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Domain\Kyc\Enum\DocumentStatus;
use App\Domain\Port\DocumentStorageInterface;
use App\Domain\Shared\Port\RealTimeNotifierInterface;
use App\Domain\User\Entity\User;
use App\Infrastructure\Compliance\Handler\GenerateDerPdfHandler;
use App\Infrastructure\Compliance\Message\GenerateDerPdfMessage;
use App\Infrastructure\Pdf\PdfGeneratorInterface;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Uid\Uuid;

final class GenerateDerPdfHandlerTest extends TestCase
{
    use ReflectionHelperTrait;

    private const string PDF = "%PDF-1.7\nder";

    private ComplianceDocumentRepositoryInterface&MockObject $documentRepository;
    private PdfGeneratorInterface&Stub $pdfGenerator;
    private DocumentStorageInterface&MockObject $storage;
    private RealTimeNotifierInterface&Stub $notifier;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private GenerateDerPdfHandler $handler;

    protected function setUp(): void
    {
        $this->documentRepository = $this->createMock(ComplianceDocumentRepositoryInterface::class);
        $this->pdfGenerator = $this->createStub(PdfGeneratorInterface::class);
        $this->storage = $this->createMock(DocumentStorageInterface::class);
        $this->notifier = $this->createStub(RealTimeNotifierInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $assembler = $this->createStub(ComplianceFolderShowAssembler::class);
        $assembler->method('assemble')->willReturn($this->createStub(ComplianceFolderShowResponse::class));

        $this->handler = new GenerateDerPdfHandler(
            $this->documentRepository,
            $this->pdfGenerator,
            new NullLogger(),
            $this->storage,
            $this->notifier,
            $assembler,
            $this->eventDispatcher,
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function document(array $overrides = []): ComplianceDocument
    {
        $folder = $this->createEntityState(BusinessFolder::class, ['slugId' => 'comp_fol_1', 'reference' => 'DOS-1', 'history' => []]);

        return $this->createEntityState(ComplianceDocument::class, array_merge([
            'id' => Uuid::v7(),
            'folder' => $folder,
            'acknowledgements' => new ArrayCollection(),
        ], $overrides));
    }

    public function testGeneratesStoresAndDispatchesThePdfGeneratedEvent(): void
    {
        $document = $this->document();
        $this->documentRepository->method('findById')->willReturn($document);
        $this->pdfGenerator->method('generateFromHtml')->willReturn(self::PDF);
        $this->storage->method('store')->willReturn('documents/der/comp_fol_1/der.pdf');

        $this->documentRepository->expects($this->once())->method('save')->with($document);
        $this->storage->expects($this->never())->method('delete');
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (DerPdfGeneratedEvent $e): bool => $document->id->toString() === $e->getDocumentId()))
            ->willReturnArgument(0);

        ($this->handler)(new GenerateDerPdfMessage($document->id->toString()));

        self::assertSame(DocumentStatus::GENERATED, $document->status);
        self::assertSame('documents/der/comp_fol_1/der.pdf', $document->storagePath);
    }

    public function testDeletesTheOldPdfWhenNotReferencedByAnyAcknowledgement(): void
    {
        $document = $this->document();
        $this->documentRepository->method('findById')->willReturn($document);
        $this->pdfGenerator->method('generateFromHtml')->willReturn(self::PDF);
        $this->storage->method('store')->willReturn('documents/der/comp_fol_1/new.pdf');

        $this->documentRepository->expects($this->once())->method('save')->with($document);
        $this->storage->expects($this->once())->method('delete')->with('documents/der/comp_fol_1/old.pdf');
        $this->eventDispatcher->expects($this->once())->method('dispatch')->willReturnArgument(0);

        ($this->handler)(new GenerateDerPdfMessage($document->id->toString(), 'documents/der/comp_fol_1/old.pdf'));
    }

    public function testDoesNotDeleteTheOldPdfWhenAnAcknowledgementReferencesIt(): void
    {
        $document = $this->document();
        $revokedAcknowledgement = DerAcknowledgement::record(
            document: $document,
            pdfSha256: str_repeat('a', 64),
            pdfStoragePath: 'documents/der/comp_fol_1/old.pdf',
            declaredName: 'Alice',
            recipientEmail: 'a@b.test',
            statement: DerStatement::current(),
        );
        $revokedAcknowledgement->revoke($this->createEntityState(User::class, ['firstName' => 'Marie', 'lastName' => 'Curie']), 'motif');
        $document->acknowledgements->add($revokedAcknowledgement);

        $this->documentRepository->method('findById')->willReturn($document);
        $this->pdfGenerator->method('generateFromHtml')->willReturn(self::PDF);
        $this->storage->method('store')->willReturn('documents/der/comp_fol_1/new.pdf');

        $this->documentRepository->expects($this->once())->method('save')->with($document);
        $this->storage->expects($this->never())->method('delete');
        $this->eventDispatcher->expects($this->once())->method('dispatch')->willReturnArgument(0);

        ($this->handler)(new GenerateDerPdfMessage($document->id->toString(), 'documents/der/comp_fol_1/old.pdf'));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testConvertsAGuardDomainExceptionIntoAnUnrecoverableException(): void
    {
        // 🛡️ Simule la garde ComplianceDocument::guardNotSealed() : un accusé de
        // réception en vigueur est arrivé entre-temps (concurrence), markAsGenerated()
        // lève alors un \DomainException que le handler doit convertir.
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
        $this->pdfGenerator->method('generateFromHtml')->willReturn(self::PDF);
        $this->storage->method('store')->willReturn('documents/der/comp_fol_1/new.pdf');

        $this->documentRepository->expects($this->never())->method('save');
        $this->storage->expects($this->never())->method('delete');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(UnrecoverableMessageHandlingException::class);
        ($this->handler)(new GenerateDerPdfMessage($document->id->toString(), 'p.pdf'));

        self::assertNotSame(DocumentStatus::FAILED, $document->status);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMarksAsFailedAndRethrowsOnGenericFailure(): void
    {
        $document = $this->document();
        $this->documentRepository->method('findById')->willReturn($document);
        $this->pdfGenerator->method('generateFromHtml')->willReturn('<html>oops</html>');

        $this->documentRepository->expects($this->once())->method('save')->with($document);
        $this->storage->expects($this->never())->method('delete');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        try {
            ($this->handler)(new GenerateDerPdfMessage($document->id->toString()));
            self::fail('Une exception aurait dû être levée.');
        } catch (\Exception $e) {
            self::assertNotInstanceOf(UnrecoverableMessageHandlingException::class, $e);
        }

        self::assertSame(DocumentStatus::FAILED, $document->status);
    }
}
