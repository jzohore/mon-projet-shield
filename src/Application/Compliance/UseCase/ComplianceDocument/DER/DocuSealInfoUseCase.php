<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Application\Compliance\DTO\Request\DocuSealInfo;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use Webmozart\Assert\Assert;

readonly class DocuSealInfoUseCase
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $documentRepository,
    ) {
    }

    public function __invoke(?ComplianceFolder $complianceFolder): ?DocuSealInfo
    {
        Assert::notNull($complianceFolder);
        $document = $this->documentRepository->findDerByFolder($complianceFolder);
        if (!$document instanceof ComplianceDocument) {
            return null;
        }

        return DocuSealInfo::fromEntity($document);
    }
}
