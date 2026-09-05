<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Compliance\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Compliance\Event\DocumentOcrProcessedEvent;
use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Compliance\Listener\AuditLogDocumentOcrProcessedListener;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AuditLogDocumentOcrProcessedListenerTest extends TestCase
{
    use ReflectionHelperTrait;

    private AuditLogRepositoryInterface&MockObject $auditLogRepository;
    private AuditLogDocumentOcrProcessedListener $listener;

    protected function setUp(): void
    {
        $this->auditLogRepository = $this->createMock(AuditLogRepositoryInterface::class);
        $this->listener = new AuditLogDocumentOcrProcessedListener($this->auditLogRepository);
    }

    /**
     * @param list<string> $findings
     */
    private function event(array $findings, bool $extractionSucceeded): DocumentOcrProcessedEvent
    {
        $workspace = $this->createEntityState(Workspace::class, ['name' => 'Cabinet Durand', 'slugId' => 'wrk_1']);
        $folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'reference' => 'DOS-2026-018',
            'workspace' => $workspace,
        ]);
        $document = $this->createEntityState(ComplianceDocument::class, [
            'id' => Uuid::v7(),
            'slugId' => 'comp_doc_1',
            'folder' => $folder,
            'type' => DocumentType::PROOF_OF_ADDRESS,
            'ocrValidatorVersion' => '2026-09-01',
            'acknowledgements' => new ArrayCollection(),
        ]);

        return new DocumentOcrProcessedEvent($document, $findings, $extractionSucceeded);
    }

    public function testWritesAnAuditLogAttributedToTheOcrSystem(): void
    {
        $event = $this->event(['point A', 'point B'], true);

        $this->auditLogRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (AuditLog $log): bool => AuditEventType::KYC_DOCUMENT_OCR_PROCESSED === $log->eventName
                && 'Cabinet Durand' === $log->workspace?->name
                && 'system_ocr' === $log->payload['actor_type']
                && true === $log->payload['extraction_succeeded']
                && 2 === $log->payload['findings_count']
                && 'proof_of_address' === $log->payload['document_type']
                && 'comp_fol_1' === $log->payload['folder_slug_id']
                && '2026-09-01' === $log->payload['validator_version']
                && $event->document->id->toString() === $log->payload['document_id']));

        ($this->listener)($event);
    }

    public function testRecordsZeroFindingsAndFailedExtraction(): void
    {
        $event = $this->event([], false);

        $this->auditLogRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (AuditLog $log): bool => 0 === $log->payload['findings_count']
                && false === $log->payload['extraction_succeeded']));

        ($this->listener)($event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenDocumentHasNoId(): void
    {
        $workspace = $this->createEntityState(Workspace::class, ['name' => 'Cabinet Durand', 'slugId' => 'wrk_1']);
        $folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'reference' => 'DOS-2026-018',
            'workspace' => $workspace,
        ]);
        $document = $this->createEntityState(ComplianceDocument::class, [
            'slugId' => 'comp_doc_1',
            'folder' => $folder,
            'type' => DocumentType::PROOF_OF_ADDRESS,
            'acknowledgements' => new ArrayCollection(),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        ($this->listener)(new DocumentOcrProcessedEvent($document, [], true));
    }
}
