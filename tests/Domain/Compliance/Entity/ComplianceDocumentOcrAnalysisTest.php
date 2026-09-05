<?php

declare(strict_types=1);

namespace App\Tests\Domain\Compliance\Entity;

use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Kyc\Enum\DocumentStatus;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class ComplianceDocumentOcrAnalysisTest extends TestCase
{
    use ReflectionHelperTrait;

    /**
     * @param array<string, mixed> $overrides
     */
    private function document(array $overrides = []): ComplianceDocument
    {
        $folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'reference' => 'DOS-1',
            'history' => [],
        ]);

        return $this->createEntityState(ComplianceDocument::class, array_merge([
            'slugId' => 'comp_doc_1',
            'folder' => $folder,
            'type' => DocumentType::PROOF_OF_ADDRESS,
            'status' => DocumentStatus::UPLOADED,
            'acknowledgements' => new ArrayCollection(),
        ], $overrides));
    }

    public function testStoresExtractedDataWithoutFindingsAndKeepsDocumentUploaded(): void
    {
        $document = $this->document(['status' => DocumentStatus::PROCESSING]);
        $data = ['first_name' => 'Jean', 'date_of_expiry' => '2999-12-31'];

        $document->attachOcrAnalysis($data, [], 'v1');

        self::assertSame($data, $document->ocrData);
        self::assertNull($document->ocrFindings);
        self::assertSame('v1', $document->ocrValidatorVersion);
        self::assertSame(DocumentStatus::UPLOADED, $document->status);
        self::assertNull($document->rejectionReason);
        self::assertCount(1, $document->folder->history);
    }

    public function testStoresFindingsAndNullDataWhenExtractionFailed(): void
    {
        $document = $this->document();

        $document->attachOcrAnalysis(null, ['a', 'b'], 'v1');

        self::assertSame(['a', 'b'], $document->ocrFindings);
        self::assertNull($document->ocrData);
        self::assertSame('v1', $document->ocrValidatorVersion);
        self::assertSame(DocumentStatus::UPLOADED, $document->status);
    }

    public function testEmptyFindingsListIsNormalizedToNull(): void
    {
        $document = $this->document();

        $document->attachOcrAnalysis(['x' => 'y'], []);

        self::assertNull($document->ocrFindings);
        self::assertNull($document->ocrValidatorVersion);
    }

    public function testClearsAnyPreviousRejectionReason(): void
    {
        $document = $this->document([
            'status' => DocumentStatus::REJECTED,
            'rejectionReason' => 'Ancien motif humain',
        ]);

        $document->attachOcrAnalysis(['x' => 'y'], ['point de vigilance'], 'v1');

        self::assertNull($document->rejectionReason);
        self::assertSame(DocumentStatus::UPLOADED, $document->status);
    }
}
