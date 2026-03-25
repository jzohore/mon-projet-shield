<?php

namespace App\Application\Kyc\UseCase;

use App\Domain\Kyc\Repository\StakeholderRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class RemoveStakeHolderUseCase
{
    public function __construct(
        private StakeholderRepositoryInterface $stakeholderRepository,
    ) {}

    public function __invoke(string $slugId): void
    {
        $currentKycFolder = $this->stakeholderRepository->findBySlugId($slugId);
        Assert::notnull($currentKycFolder, 'Le dossier KYC n\'existe pas');

        $this->stakeholderRepository->remove($currentKycFolder);
    }
}
