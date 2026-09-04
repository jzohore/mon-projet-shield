<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Application\Compliance\UseCase\ComplianceDocument\DER\DeclineDerUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Event\DerDeclinedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Domain\Database\TransactionManagerInterface;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class DeclineDerUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private const string TOKEN = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';

    private ComplianceDocumentRepositoryInterface&Stub $documentRepository;
    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private DeclineDerUseCase $useCase;

    protected function setUp(): void
    {
        $this->documentRepository = $this->createStub(ComplianceDocumentRepositoryInterface::class);
        $this->folderRepository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $transactionManager->method('transactional')->willReturnCallback(
            static fn (callable $operation): mixed => $operation()
        );

        $this->useCase = new DeclineDerUseCase(
            $this->documentRepository,
            $this->folderRepository,
            $transactionManager,
            $this->eventDispatcher,
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

    public function testRecordsTheRefusalAndRejectsTheFolder(): void
    {
        $document = $this->document();
        $this->documentRepository->method('findOneByAcknowledgementTokenHash')->willReturn($document);

        $this->folderRepository->expects($this->once())->method('save');
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (DerDeclinedEvent $e): bool => 'Ce n\'est pas mon cabinet' === $e->getReason()))
            ->willReturnArgument(0);

        ($this->useCase)(self::TOKEN, "Ce n'est pas mon cabinet");

        self::assertTrue($document->isDerDeclined());
        self::assertSame(ComplianceFolderStatus::DER_REJECTED, $document->folder->status);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRefusesWhenTheDerIsAlreadyAcknowledged(): void
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
        $this->documentRepository->method('findOneByAcknowledgementTokenHash')->willReturn($document);

        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        ($this->useCase)(self::TOKEN, null);
    }

    public function testIsIdempotentWhenAlreadyDeclined(): void
    {
        $document = $this->document(['derDeclinedAt' => new \DateTimeImmutable('-1 hour')]);
        $this->documentRepository->method('findOneByAcknowledgementTokenHash')->willReturn($document);

        $this->folderRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        ($this->useCase)(self::TOKEN, 'motif');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenTheTokenIsUnknown(): void
    {
        $this->documentRepository->method('findOneByAcknowledgementTokenHash')->willReturn(null);

        $this->expectException(\DomainException::class);
        ($this->useCase)(self::TOKEN, null);
    }
}
