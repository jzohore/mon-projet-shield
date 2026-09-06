<?php

declare(strict_types=1);

namespace App\Tests\Domain\Compliance\Entity;

use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class ComplianceFolderRetentionTest extends TestCase
{
    use ReflectionHelperTrait;

    /**
     * @param array<string, mixed> $overrides
     */
    private function folder(ComplianceFolderStatus $status = ComplianceFolderStatus::APPROVED, array $overrides = []): BusinessFolder
    {
        return $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'reference' => 'DOS-1',
            'status' => $status,
            'history' => [],
            'isUnderLegalHold' => false,
            'documents' => new ArrayCollection(),
            'meetingRecordings' => new ArrayCollection(),
            ...$overrides,
        ]);
    }

    public function testEndBusinessRelationshipStartsTheRetentionClock(): void
    {
        $folder = $this->folder();

        $folder->endBusinessRelationship('  Départ du client  ', 'Marie Curie');

        self::assertNotNull($folder->relationshipEndedAt);
        self::assertSame('Départ du client', $folder->relationshipEndReason);
        self::assertNotNull($folder->purgeDueAt);

        $expected = $folder->relationshipEndedAt->modify('+' . ComplianceFolder::RETENTION_YEARS . ' years');
        self::assertSame($expected->format('Y-m-d'), $folder->purgeDueAt->format('Y-m-d'));
        self::assertNotSame([], $folder->history);
    }

    public function testEndBusinessRelationshipIsNotReplayable(): void
    {
        $folder = $this->folder();
        $folder->endBusinessRelationship('motif', 'Marie Curie');

        $this->expectException(\DomainException::class);
        $folder->endBusinessRelationship('autre motif', 'Marie Curie');
    }

    public function testEndBusinessRelationshipRejectsABlankReason(): void
    {
        $this->expectException(\DomainException::class);
        $this->folder()->endBusinessRelationship('   ', 'Marie Curie');
    }

    public function testEndBusinessRelationshipRejectsADraftFolder(): void
    {
        $this->expectException(\DomainException::class);
        $this->folder(ComplianceFolderStatus::DRAFT)->endBusinessRelationship('motif', 'Marie Curie');
    }

    public function testLegalHoldBlocksPurgeEvenWhenDue(): void
    {
        $folder = $this->folder(overrides: [
            'relationshipEndedAt' => new \DateTimeImmutable('-10 years'),
            'purgeDueAt' => new \DateTimeImmutable('-5 years'),
        ]);

        self::assertTrue($folder->isPurgeDue());

        $folder->placeLegalHold('Contentieux en cours', 'Marie Curie');

        self::assertTrue($folder->isUnderLegalHold);
        self::assertFalse($folder->isPurgeDue());

        $folder->liftLegalHold('Marie Curie');

        self::assertFalse($folder->isUnderLegalHold);
        self::assertTrue($folder->isPurgeDue());
    }

    public function testIsPurgeDueIsFalseWhileTheRelationshipIsOngoing(): void
    {
        self::assertFalse($this->folder()->isPurgeDue());
    }

    public function testCannotLiftALegalHoldThatIsNotSet(): void
    {
        $this->expectException(\DomainException::class);
        $this->folder()->liftLegalHold('Marie Curie');
    }
}
