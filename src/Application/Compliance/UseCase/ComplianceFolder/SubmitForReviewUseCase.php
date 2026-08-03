<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;

readonly class SubmitForReviewUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $complianceFolderRepository,
    ) {
    }

    public function __invoke(ComplianceFolder $complianceFolder): void
    {
        $complianceFolder->submitForReview();
        $this->complianceFolderRepository->save($complianceFolder);
    }
}
