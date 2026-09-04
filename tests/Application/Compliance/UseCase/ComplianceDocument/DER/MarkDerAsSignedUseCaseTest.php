<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Application\Compliance\UseCase\ComplianceDocument\DER\MarkDerAsSignedUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Event\DerSignedEvent;
use App\Domain\Compliance\Exception\Document\DocumentNotFoundException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class MarkDerAsSignedUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private const string SUBMISSION_ID = '4242';
    private const string DOCUMENT_URL = 'https://docuseal.test/documents/4242/signed.pdf';
    private const string AUDIT_LOG_URL = 'https://docuseal.test/submissions/4242/audit.pdf';

    private ComplianceDocumentRepositoryInterface&MockObject $repository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private MarkDerAsSignedUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ComplianceDocumentRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->useCase = new MarkDerAsSignedUseCase($this->repository, $this->eventDispatcher);
    }

    private function completedAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-02-03 09:30:00', new \DateTimeZone('Europe/Paris'));
    }

    private function document(?\DateTimeImmutable $signedAt): ComplianceDocument
    {
        $folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'history' => [],
        ]);

        return $this->createEntityState(ComplianceDocument::class, [
            'slugId' => 'comp_doc_1',
            'folder' => $folder,
            'docuSealSubmissionId' => (int) self::SUBMISSION_ID,
            'docuSealSignedAt' => $signedAt,
        ]);
    }

    public function testFreezesDocuSealReferencesAndDispatchesEvent(): void
    {
        $document = $this->document(signedAt: null);

        $this->repository->method('findBySubmissionId')->willReturn($document);
        $this->repository->expects($this->once())->method('save')->with($document);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (DerSignedEvent $event): bool => self::SUBMISSION_ID === $event->getSubmissionId()
                && self::DOCUMENT_URL === $event->getDocumentUrl()
                && self::AUDIT_LOG_URL === $event->getAuditLogUrl()))
            ->willReturnArgument(0);

        ($this->useCase)(self::SUBMISSION_ID, self::DOCUMENT_URL, self::AUDIT_LOG_URL, $this->completedAt());

        self::assertTrue($document->isDocuSealSigned());
        self::assertSame(self::DOCUMENT_URL, $document->docuSealDocumentUrl);
        self::assertSame(self::AUDIT_LOG_URL, $document->docuSealAuditLogUrl);
        self::assertSame(ComplianceFolderStatus::AWAITING_CLIENT, $document->folder->status);
    }

    public function testIsIdempotentWhenDocumentIsAlreadySigned(): void
    {
        $document = $this->document(signedAt: new \DateTimeImmutable('2026-01-01', new \DateTimeZone('Europe/Paris')));

        $this->repository->method('findBySubmissionId')->willReturn($document);
        $this->repository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        ($this->useCase)(self::SUBMISSION_ID, self::DOCUMENT_URL, self::AUDIT_LOG_URL, $this->completedAt());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenDocumentIsUnknown(): void
    {
        $this->repository->method('findBySubmissionId')->willReturn(null);
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(DocumentNotFoundException::class);

        ($this->useCase)(self::SUBMISSION_ID, self::DOCUMENT_URL, self::AUDIT_LOG_URL, $this->completedAt());
    }
}
