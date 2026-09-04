<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Compliance\Listener\DER;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Event\DerAcknowledgedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Compliance\Listener\DER\AuditLogDerAcknowledgedListener;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AuditLogDerAcknowledgedListenerTest extends TestCase
{
    use ReflectionHelperTrait;

    private AuditLogRepositoryInterface&MockObject $auditLogRepository;
    private ComplianceDocumentRepositoryInterface&Stub $documentRepository;
    private AuditLogDerAcknowledgedListener $listener;

    protected function setUp(): void
    {
        $this->auditLogRepository = $this->createMock(AuditLogRepositoryInterface::class);
        $this->documentRepository = $this->createStub(ComplianceDocumentRepositoryInterface::class);
        $this->listener = new AuditLogDerAcknowledgedListener($this->auditLogRepository, $this->documentRepository);
    }

    private function event(): DerAcknowledgedEvent
    {
        return new DerAcknowledgedEvent(
            documentId: 'doc-uuid',
            folderSlugId: 'comp_fol_1',
            acknowledgementSlugId: 'der_ack_abc',
            declaredName: 'Alice Martin',
            acknowledgedAt: new \DateTimeImmutable('2026-02-03 09:30:00'),
            pdfSha256: str_repeat('a', 64),
        );
    }

    private function document(Workspace $workspace): ComplianceDocument
    {
        $folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'reference' => 'DOS-2026-018',
            'workspace' => $workspace,
        ]);

        return $this->createEntityState(ComplianceDocument::class, [
            'id' => Uuid::v7(),
            'folder' => $folder,
            'acknowledgements' => new ArrayCollection(),
        ]);
    }

    public function testWritesAnAuditLogAttributedToTheClientWithTheProofHash(): void
    {
        $workspace = $this->createEntityState(Workspace::class, ['name' => 'Cabinet Durand', 'slugId' => 'wrk_1']);
        $this->documentRepository->method('findById')->willReturn($this->document($workspace));

        $this->auditLogRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (AuditLog $log): bool => AuditEventType::DER_ACKNOWLEDGED === $log->eventName
                && 'Cabinet Durand' === $log->workspace?->name
                && 'client' === $log->payload['actor_type']
                && 'Alice Martin' === $log->payload['actor_name']
                && str_repeat('a', 64) === $log->payload['pdf_sha256']
                && 'DOS-2026-018' === $log->payload['document_reference']
                && 'der_ack_abc' === $log->payload['acknowledgement_slug_id']));

        ($this->listener)($this->event());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenTheDocumentIsUnknown(): void
    {
        $this->documentRepository->method('findById')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        ($this->listener)($this->event());
    }
}
