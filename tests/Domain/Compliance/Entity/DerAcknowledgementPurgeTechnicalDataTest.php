<?php

declare(strict_types=1);

namespace App\Tests\Domain\Compliance\Entity;

use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class DerAcknowledgementPurgeTechnicalDataTest extends TestCase
{
    use ReflectionHelperTrait;

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
            ipAddress: '203.0.113.7',
            userAgent: 'Mozilla/5.0',
        );
    }

    public function testPurgeClearsIpAndUserAgentAndStampsTheDate(): void
    {
        $acknowledgement = $this->acknowledgement();
        self::assertSame('203.0.113.7', $acknowledgement->ipAddress);

        $acknowledgement->purgeTechnicalData();

        self::assertNull($acknowledgement->ipAddress);
        self::assertNull($acknowledgement->userAgent);
        self::assertTrue($acknowledgement->isTechnicalDataPurged());
        self::assertNotNull($acknowledgement->technicalDataPurgedAt);
    }

    public function testPurgeIsIdempotent(): void
    {
        $acknowledgement = $this->acknowledgement();
        $acknowledgement->purgeTechnicalData();
        $firstPurgedAt = $acknowledgement->technicalDataPurgedAt;

        $acknowledgement->purgeTechnicalData();

        self::assertSame($firstPurgedAt, $acknowledgement->technicalDataPurgedAt);
    }
}
