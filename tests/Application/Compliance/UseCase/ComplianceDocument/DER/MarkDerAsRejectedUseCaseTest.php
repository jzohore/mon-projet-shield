<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Application\Compliance\UseCase\ComplianceDocument\DER\MarkDerAsRejectedUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Event\DerRejectedEvent;
use App\Domain\Compliance\Exception\Document\DocumentNotFoundException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class MarkDerAsRejectedUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private const string SUBMISSION_ID = '4242';

    private ComplianceDocumentRepositoryInterface&MockObject $repository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private MarkDerAsRejectedUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ComplianceDocumentRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->useCase = new MarkDerAsRejectedUseCase($this->repository, $this->eventDispatcher);
    }

    private function declinedAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-02-03 09:30:00', new \DateTimeZone('Europe/Paris'));
    }

    private function document(?\DateTimeImmutable $declinedAt): ComplianceDocument
    {
        $folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'history' => [],
        ]);

        return $this->createEntityState(ComplianceDocument::class, [
            'slugId' => 'comp_doc_1',
            'folder' => $folder,
            'docuSealDeclinedAt' => $declinedAt,
        ]);
    }

    public function testRecordsTheRefusalAndDispatchesEvent(): void
    {
        $document = $this->document(declinedAt: null);

        $this->repository->method('findBySubmissionId')->willReturn($document);
        $this->repository->expects($this->once())->method('save')->with($document);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (DerRejectedEvent $event): bool => 'Indisponible' === $event->getDeclineReason()))
            ->willReturnArgument(0);

        ($this->useCase)(self::SUBMISSION_ID, $this->declinedAt(), 'Indisponible');

        self::assertTrue($document->isDocuSealDeclined());
        self::assertSame('Indisponible', $document->docuSealRejectedReason);
        self::assertSame(ComplianceFolderStatus::DER_REJECTED, $document->folder->status);
    }

    public function testIsIdempotentWhenTheRefusalWasAlreadyRecorded(): void
    {
        $document = $this->document(declinedAt: new \DateTimeImmutable('2026-01-01', new \DateTimeZone('Europe/Paris')));

        $this->repository->method('findBySubmissionId')->willReturn($document);
        $this->repository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        ($this->useCase)(self::SUBMISSION_ID, $this->declinedAt(), 'Autre motif');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenDocumentIsUnknown(): void
    {
        $this->repository->method('findBySubmissionId')->willReturn(null);
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(DocumentNotFoundException::class);

        ($this->useCase)(self::SUBMISSION_ID, $this->declinedAt(), null);
    }
}
