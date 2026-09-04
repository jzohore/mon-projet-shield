<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Compliance\Handler;

use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Repository\DerAcknowledgementRepositoryInterface;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Domain\Port\DocumentStorageInterface;
use App\Infrastructure\Compliance\Handler\GenerateDerAcknowledgementCertificateHandler;
use App\Infrastructure\Compliance\Message\GenerateDerAcknowledgementCertificateMessage;
use App\Infrastructure\Pdf\PdfGeneratorInterface;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class GenerateDerAcknowledgementCertificateHandlerTest extends TestCase
{
    use ReflectionHelperTrait;

    private const string PDF = "%PDF-1.7\nattestation";

    private DerAcknowledgementRepositoryInterface&MockObject $acknowledgementRepository;
    private PdfGeneratorInterface&Stub $pdfGenerator;
    private DocumentStorageInterface&Stub $storage;
    private GenerateDerAcknowledgementCertificateHandler $handler;

    protected function setUp(): void
    {
        $this->acknowledgementRepository = $this->createMock(DerAcknowledgementRepositoryInterface::class);
        $this->pdfGenerator = $this->createStub(PdfGeneratorInterface::class);
        $this->storage = $this->createStub(DocumentStorageInterface::class);

        $this->handler = new GenerateDerAcknowledgementCertificateHandler(
            $this->acknowledgementRepository,
            $this->pdfGenerator,
            $this->storage,
            new NullLogger(),
        );
    }

    private function acknowledgement(): DerAcknowledgement
    {
        $folder = $this->createEntityState(BusinessFolder::class, ['slugId' => 'comp_fol_1', 'reference' => 'DOS-1', 'history' => []]);
        $document = $this->createEntityState(ComplianceDocument::class, ['folder' => $folder, 'acknowledgements' => new ArrayCollection()]);

        return DerAcknowledgement::record(
            document: $document,
            pdfSha256: str_repeat('a', 64),
            pdfStoragePath: 'p.pdf',
            declaredName: 'Alice Martin',
            recipientEmail: 'client@acme.test',
            statement: DerStatement::current(),
        );
    }

    public function testRendersStoresAndAttachesTheCertificate(): void
    {
        $acknowledgement = $this->acknowledgement();
        $this->acknowledgementRepository->method('findBySlugId')->willReturn($acknowledgement);
        $this->pdfGenerator->method('generateFromHtml')->willReturn(self::PDF);
        $this->storage->method('store')->willReturn('documents/der/comp_fol_1/certificate/attestation.pdf');

        $this->acknowledgementRepository->expects($this->once())->method('save')->with($acknowledgement);

        ($this->handler)(new GenerateDerAcknowledgementCertificateMessage($acknowledgement->slugId));

        self::assertTrue($acknowledgement->hasCertificate());
        self::assertSame(hash('sha256', self::PDF), $acknowledgement->certificateSha256);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testIsIdempotentWhenTheCertificateAlreadyExists(): void
    {
        $acknowledgement = $this->acknowledgement();
        $acknowledgement->attachCertificate('already/there.pdf', str_repeat('b', 64));
        $this->acknowledgementRepository->method('findBySlugId')->willReturn($acknowledgement);

        $this->acknowledgementRepository->expects($this->never())->method('save');

        ($this->handler)(new GenerateDerAcknowledgementCertificateMessage($acknowledgement->slugId));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenGotenbergDoesNotReturnAPdf(): void
    {
        $acknowledgement = $this->acknowledgement();
        $this->acknowledgementRepository->method('findBySlugId')->willReturn($acknowledgement);
        $this->pdfGenerator->method('generateFromHtml')->willReturn('<html>oops</html>');

        $this->expectException(\RuntimeException::class);
        ($this->handler)(new GenerateDerAcknowledgementCertificateMessage($acknowledgement->slugId));
    }
}
