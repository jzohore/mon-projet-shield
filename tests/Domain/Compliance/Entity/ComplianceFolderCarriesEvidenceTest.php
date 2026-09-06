<?php

declare(strict_types=1);

namespace App\Tests\Domain\Compliance\Entity;

use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Enum\DocumentType;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * {@see ComplianceFolder::carriesEvidence()} : la frontière entre un dossier
 * vierge (retirable, supprimable) et un dossier engagé (clôturable, conservé 5 ans).
 */
final class ComplianceFolderCarriesEvidenceTest extends TestCase
{
    use ReflectionHelperTrait;

    /**
     * @param iterable<ComplianceDocument>|null $documents
     */
    private function draft(
        ?\DateTimeImmutable $submittedAt = null,
        ?iterable $documents = null,
        bool $withRecording = false,
    ): BusinessFolder {
        return $this->createEntityState(BusinessFolder::class, [
            'status' => ComplianceFolderStatus::DRAFT,
            'submittedAt' => $submittedAt,
            'documents' => new ArrayCollection($documents ? iterator_to_array($documents, false) : []),
            'meetingRecordings' => new ArrayCollection($withRecording ? [new \stdClass()] : []),
        ]);
    }

    private function document(DocumentType $type, ?string $storagePath): ComplianceDocument
    {
        return $this->createEntityState(ComplianceDocument::class, [
            'type' => $type,
            'storagePath' => $storagePath,
        ]);
    }

    public function testAnEmptyDraftCarriesNoEvidence(): void
    {
        self::assertFalse($this->draft()->carriesEvidence());
    }

    public function testADraftWithOnlyExpectedEmptyDocumentsCarriesNoEvidence(): void
    {
        $folder = $this->draft(documents: [
            $this->document(DocumentType::ID_CARD, null),
            $this->document(DocumentType::PROOF_OF_ADDRESS, null),
        ]);

        self::assertFalse($folder->carriesEvidence());
    }

    public function testASubmittedDraftCarriesEvidence(): void
    {
        self::assertTrue($this->draft(submittedAt: new \DateTimeImmutable())->carriesEvidence());
    }

    public function testADraftWithAMeetingRecordingCarriesEvidence(): void
    {
        self::assertTrue($this->draft(withRecording: true)->carriesEvidence());
    }

    public function testADraftCarryingADerCarriesEvidence(): void
    {
        $folder = $this->draft(documents: [$this->document(DocumentType::DER, null)]);

        self::assertTrue($folder->carriesEvidence());
    }

    public function testADraftWithAnUploadedDocumentCarriesEvidence(): void
    {
        $folder = $this->draft(documents: [$this->document(DocumentType::ID_CARD, 's3://bucket/id.pdf')]);

        self::assertTrue($folder->carriesEvidence());
    }

    public function testAnyNonDraftStatusCarriesEvidence(): void
    {
        foreach (ComplianceFolderStatus::cases() as $status) {
            if (ComplianceFolderStatus::DRAFT === $status) {
                continue;
            }
            $folder = $this->createEntityState(BusinessFolder::class, ['status' => $status]);
            self::assertTrue($folder->carriesEvidence(), $status->value . ' doit porter une preuve');
        }
    }
}
