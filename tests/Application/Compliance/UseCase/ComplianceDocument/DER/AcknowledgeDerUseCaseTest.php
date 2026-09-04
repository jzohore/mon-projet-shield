<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Application\Compliance\DTO\Request\AcknowledgeDerRequest;
use App\Application\Compliance\DTO\Response\ComplianceFolderShowResponse;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\AcknowledgeDerUseCase;
use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Application\User\UseCase\Client\ProvisionClientForFolderUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Compliance\Event\DerAcknowledgedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Repository\DerAcknowledgementRepositoryInterface;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Kyc\Enum\DocumentStatus;
use App\Domain\Port\DocumentStorageInterface;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class AcknowledgeDerUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private const string TOKEN = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';
    private const string PDF = "%PDF-1.7\nDER du cabinet";

    private ComplianceDocumentRepositoryInterface&Stub $documentRepository;
    private DerAcknowledgementRepositoryInterface&MockObject $acknowledgementRepository;
    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private ComplianceFolderShowAssembler&Stub $folderShowAssembler;
    private DocumentStorageInterface&Stub $storage;
    private ProvisionClientForFolderUseCase&MockObject $provisionClient;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private AcknowledgeDerUseCase $useCase;

    protected function setUp(): void
    {
        $this->documentRepository = $this->createStub(ComplianceDocumentRepositoryInterface::class);
        $this->acknowledgementRepository = $this->createMock(DerAcknowledgementRepositoryInterface::class);
        $this->folderRepository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->folderShowAssembler = $this->createStub(ComplianceFolderShowAssembler::class);
        $this->storage = $this->createStub(DocumentStorageInterface::class);
        $this->provisionClient = $this->createMock(ProvisionClientForFolderUseCase::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $transactionManager->method('transactional')->willReturnCallback(
            static fn (callable $operation): mixed => $operation()
        );

        $this->useCase = new AcknowledgeDerUseCase(
            $this->documentRepository,
            $this->acknowledgementRepository,
            $this->folderRepository,
            $this->folderShowAssembler,
            $this->storage,
            $this->provisionClient,
            $transactionManager,
            $this->eventDispatcher,
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function document(array $overrides = []): ComplianceDocument
    {
        $folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'reference' => 'DOS-2026-018',
            'history' => [],
        ]);

        return $this->createEntityState(ComplianceDocument::class, array_merge([
            'id' => \Symfony\Component\Uid\Uuid::v7(),
            'slugId' => 'comp_doc_1',
            'type' => DocumentType::DER,
            'folder' => $folder,
            'status' => DocumentStatus::GENERATED,
            'storagePath' => 'documents/der/comp_fol_1/der.pdf',
            'acknowledgementTokenHash' => hash('sha256', self::TOKEN),
            'acknowledgementTokenExpiresAt' => new \DateTimeImmutable('+20 days'),
            'acknowledgements' => new ArrayCollection(),
        ], $overrides));
    }

    private function request(bool $accepted = true): AcknowledgeDerRequest
    {
        $request = new AcknowledgeDerRequest();
        $request->token = self::TOKEN;
        $request->declaredName = 'Alice Martin';
        $request->accepted = $accepted;
        $request->ipAddress = '203.0.113.7';
        $request->userAgent = 'Mozilla/5.0';

        return $request;
    }

    private function assemblerReturns(?string $email): void
    {
        $this->folderShowAssembler->method('assemble')->willReturn($this->folderShowResponse($email));
    }

    private function existingAcknowledgement(ComplianceDocument $document): DerAcknowledgement
    {
        return DerAcknowledgement::record(
            document: $document,
            pdfSha256: str_repeat('a', 64),
            pdfStoragePath: 'documents/der/comp_fol_1/der.pdf',
            declaredName: 'Alice Martin',
            recipientEmail: 'client@acme.test',
            statement: DerStatement::current(),
        );
    }

    public function testRecordsTheAcknowledgementProvisionsTheClientAndDispatches(): void
    {
        $document = $this->document();
        $this->documentRepository->method('findOneByAcknowledgementTokenHash')->willReturn($document);
        $this->assemblerReturns('client@acme.test');
        $this->storage->method('getContents')->willReturn(self::PDF);

        $this->provisionClient->expects($this->once())->method('__invoke')->with($document->folder);
        $this->acknowledgementRepository->expects($this->once())->method('save');
        $this->folderRepository->expects($this->once())->method('save')->with($document->folder);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (DerAcknowledgedEvent $event): bool => 'comp_fol_1' === $event->getFolderSlugId()
                && 'Alice Martin' === $event->getDeclaredName()
                && hash('sha256', self::PDF) === $event->getPdfSha256()
                && str_starts_with($event->getAcknowledgementSlugId(), 'der_ack_')))
            ->willReturnArgument(0);

        $slugId = ($this->useCase)($this->request());

        self::assertStringStartsWith('der_ack_', $slugId);
        self::assertSame(ComplianceFolderStatus::AWAITING_CLIENT, $document->folder->status);
    }

    public function testIsIdempotentWhenTheDerIsAlreadyAcknowledged(): void
    {
        $document = $this->document();
        $existing = $this->existingAcknowledgement($document);
        $document->acknowledgements->add($existing);

        $this->documentRepository->method('findOneByAcknowledgementTokenHash')->willReturn($document);

        $this->provisionClient->expects($this->never())->method('__invoke');
        $this->acknowledgementRepository->expects($this->never())->method('save');
        $this->folderRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        self::assertSame($existing->slugId, ($this->useCase)($this->request()));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenTheCheckboxWasNotTicked(): void
    {
        $document = $this->document();
        $this->documentRepository->method('findOneByAcknowledgementTokenHash')->willReturn($document);
        $this->assemblerReturns('client@acme.test');

        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        ($this->useCase)($this->request(accepted: false));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenTheTokenIsUnknown(): void
    {
        $this->documentRepository->method('findOneByAcknowledgementTokenHash')->willReturn(null);

        $this->expectException(\DomainException::class);
        ($this->useCase)($this->request());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenTheTokenHasExpired(): void
    {
        $document = $this->document(['acknowledgementTokenExpiresAt' => new \DateTimeImmutable('-1 day')]);
        $this->documentRepository->method('findOneByAcknowledgementTokenHash')->willReturn($document);

        $this->expectException(\DomainException::class);
        ($this->useCase)($this->request());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenTheDerIsNotGeneratedYet(): void
    {
        $document = $this->document(['status' => DocumentStatus::PENDING]);
        $this->documentRepository->method('findOneByAcknowledgementTokenHash')->willReturn($document);

        $this->expectException(\DomainException::class);
        ($this->useCase)($this->request());
    }

    private function folderShowResponse(?string $email): ComplianceFolderShowResponse
    {
        return new ComplianceFolderShowResponse(
            id: 'id',
            slugId: 'comp_fol_1',
            workspaceName: 'Cabinet Durand',
            workspaceEmail: 'cabinet@durand.fr',
            reference: 'DOS-2026-018',
            statusValue: 'awaiting_client',
            statusLabel: 'En attente client',
            isManual: false,
            isKyb: true,
            isDraft: false,
            isArchived: false,
            isAcceptedRecording: false,
            method: 'flash',
            headerTitle: 'ACME',
            headerSubtitle: 'SIRET',
            contactName: 'Alice Martin',
            workspaceRemainingMinutes: 0,
            companyDocuments: [],
            individualDocuments: [],
            stakeholders: [],
            history: [],
            contactFirstName: 'Alice',
            contactLastName: 'Martin',
            contactEmail: $email,
            type: 'business',
        );
    }
}
