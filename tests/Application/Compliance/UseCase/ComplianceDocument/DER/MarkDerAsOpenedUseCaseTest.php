<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Application\Compliance\UseCase\ComplianceDocument\DER\MarkDerAsOpenedUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Exception\Document\DocumentNotFoundException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MarkDerAsOpenedUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private const string SUBMISSION_ID = '4242';

    private ComplianceDocumentRepositoryInterface&MockObject $repository;
    private MarkDerAsOpenedUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ComplianceDocumentRepositoryInterface::class);
        $this->useCase = new MarkDerAsOpenedUseCase($this->repository);
    }

    private function openedAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-02-03 09:30:00', new \DateTimeZone('Europe/Paris'));
    }

    /**
     * @param array<string, mixed> $documentState
     */
    private function document(array $documentState = []): ComplianceDocument
    {
        $folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'status' => ComplianceFolderStatus::DER_SENT,
            'history' => [],
        ]);

        return $this->createEntityState(ComplianceDocument::class, array_merge([
            'slugId' => 'comp_doc_1',
            'folder' => $folder,
            'docuSealOpenedAt' => null,
            'docuSealSignedAt' => null,
            'docuSealDeclinedAt' => null,
        ], $documentState));
    }

    public function testRecordsTheFirstConsultation(): void
    {
        $document = $this->document();

        $this->repository->method('findBySubmissionId')->willReturn($document);
        $this->repository->expects($this->once())->method('save')->with($document);

        ($this->useCase)(self::SUBMISSION_ID, openedAt: $this->openedAt());

        self::assertTrue($document->isDocuSealOpened());
        self::assertSame(ComplianceFolderStatus::DER_OPENED, $document->folder->status);
    }

    public function testIsIdempotentOnAReplayedViewedEvent(): void
    {
        $document = $this->document([
            'docuSealOpenedAt' => new \DateTimeImmutable('2026-02-01', new \DateTimeZone('Europe/Paris')),
        ]);

        $this->repository->method('findBySubmissionId')->willReturn($document);
        $this->repository->expects($this->never())->method('save');

        ($this->useCase)(self::SUBMISSION_ID, openedAt: $this->openedAt());

        self::assertSame(ComplianceFolderStatus::DER_SENT, $document->folder->status);
    }

    public function testDoesNotRegressAFolderThatHasAlreadyMovedPastOpening(): void
    {
        $document = $this->document([
            'docuSealSignedAt' => new \DateTimeImmutable('2026-02-02', new \DateTimeZone('Europe/Paris')),
        ]);
        $document->folder->markAsAwaitingClient('02/02/26 10:00');

        $this->repository->method('findBySubmissionId')->willReturn($document);
        $this->repository->expects($this->never())->method('save');

        ($this->useCase)(self::SUBMISSION_ID, openedAt: $this->openedAt());

        self::assertSame(ComplianceFolderStatus::AWAITING_CLIENT, $document->folder->status);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenDocumentIsUnknown(): void
    {
        $this->repository->method('findBySubmissionId')->willReturn(null);

        $this->expectException(DocumentNotFoundException::class);

        ($this->useCase)(self::SUBMISSION_ID, openedAt: $this->openedAt());
    }
}
