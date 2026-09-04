<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Compliance\Listener\DER;

use App\Application\Compliance\DTO\Response\ComplianceFolderShowResponse;
use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Event\DerPdfGeneratedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Domain\Database\TransactionManagerInterface;
use App\Infrastructure\Compliance\Listener\DER\SendDerAcknowledgementLinkListener;
use App\Infrastructure\Compliance\Message\SendDerSignatureMessage;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SendDerAcknowledgementLinkListenerTest extends TestCase
{
    use ReflectionHelperTrait;

    private ComplianceDocumentRepositoryInterface&MockObject $documentRepository;
    private ComplianceFolderShowAssembler&Stub $assembler;
    private MessageBusInterface&MockObject $messageBus;
    private SendDerAcknowledgementLinkListener $listener;

    protected function setUp(): void
    {
        $this->documentRepository = $this->createMock(ComplianceDocumentRepositoryInterface::class);
        $this->assembler = $this->createStub(ComplianceFolderShowAssembler::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://kysure.test/der/deadbeef');

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $transactionManager->method('transactional')->willReturnCallback(
            static fn (callable $operation): mixed => $operation()
        );

        $this->listener = new SendDerAcknowledgementLinkListener(
            $this->documentRepository,
            $this->assembler,
            $urlGenerator,
            $this->messageBus,
            $transactionManager,
            new NullLogger(),
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function document(array $overrides = []): ComplianceDocument
    {
        $folder = $this->createEntityState(BusinessFolder::class, ['slugId' => 'comp_fol_1', 'history' => []]);

        return $this->createEntityState(ComplianceDocument::class, array_merge([
            'id' => \Symfony\Component\Uid\Uuid::v7(),
            'folder' => $folder,
            'derSendRequestedAt' => new \DateTimeImmutable('-1 minute'),
            'derLinkSentAt' => null,
            'acknowledgements' => new ArrayCollection(),
        ], $overrides));
    }

    private function assemblerReturns(?string $email): void
    {
        $this->assembler->method('assemble')->willReturn(new ComplianceFolderShowResponse(
            id: 'id', slugId: 'comp_fol_1', workspaceName: 'W', workspaceEmail: 'w@w.fr',
            reference: 'DOS-1', statusValue: 's', statusLabel: 'S', isManual: false, isKyb: true,
            isDraft: false, isArchived: false, isAcceptedRecording: false, method: 'flash',
            headerTitle: 'ACME', headerSubtitle: 'SIRET', contactName: 'Alice Martin', workspaceRemainingMinutes: 0,
            companyDocuments: [], individualDocuments: [], stakeholders: [], history: [],
            contactFirstName: 'Alice', contactLastName: 'Martin', contactEmail: $email, type: 'business',
        ));
    }

    public function testIssuesTheTokenMarksTheLinkSentAndQueuesTheEmail(): void
    {
        $document = $this->document();
        $this->documentRepository->method('findById')->willReturn($document);
        $this->assemblerReturns('client@acme.test');

        $this->documentRepository->expects($this->once())->method('save')->with($document);
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (SendDerSignatureMessage $m): bool => 'client@acme.test' === $m->clientEmail
                && str_contains($m->signatureUrl, '/der/')))
            ->willReturn(new Envelope(new \stdClass()));

        ($this->listener)(new DerPdfGeneratedEvent($document->id->toString()));

        self::assertTrue($document->isAcknowledgementLinkSent());
        self::assertNotNull($document->acknowledgementTokenHash);
    }

    public function testIsIdempotentWhenTheLinkWasAlreadySent(): void
    {
        $document = $this->document(['derLinkSentAt' => new \DateTimeImmutable('-1 hour')]);
        $this->documentRepository->method('findById')->willReturn($document);

        $this->documentRepository->expects($this->never())->method('save');
        $this->messageBus->expects($this->never())->method('dispatch');

        ($this->listener)(new DerPdfGeneratedEvent($document->id->toString()));
    }

    public function testDoesNothingWhenNoSendWasRequested(): void
    {
        $document = $this->document(['derSendRequestedAt' => null]);
        $this->documentRepository->method('findById')->willReturn($document);

        $this->documentRepository->expects($this->never())->method('save');
        $this->messageBus->expects($this->never())->method('dispatch');

        ($this->listener)(new DerPdfGeneratedEvent($document->id->toString()));
    }

    public function testDoesNothingWhenAlreadyAcknowledged(): void
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

        $this->documentRepository->expects($this->never())->method('save');
        $this->messageBus->expects($this->never())->method('dispatch');

        ($this->listener)(new DerPdfGeneratedEvent($document->id->toString()));
    }

    public function testSkipsWhenTheClientEmailIsMissing(): void
    {
        $document = $this->document();
        $this->documentRepository->method('findById')->willReturn($document);
        $this->assemblerReturns(null);

        $this->documentRepository->expects($this->never())->method('save');
        $this->messageBus->expects($this->never())->method('dispatch');

        ($this->listener)(new DerPdfGeneratedEvent($document->id->toString()));

        self::assertFalse($document->isAcknowledgementLinkSent());
    }
}
