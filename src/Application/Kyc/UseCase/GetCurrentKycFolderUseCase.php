<?php

declare(strict_types=1);

namespace App\Application\Kyc\UseCase;

use App\Application\Kyc\DTO\Response\KycFolderResponse;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class GetCurrentKycFolderUseCase
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
    ) {
    }

    public function __invoke(string $slugId): KycFolderResponse
    {
        Assert::notNull($slugId);
        $kycFolder = $this->kycFolderRepository->findBySlugId(slugId: $slugId);
        Assert::notNull($kycFolder, 'Kyc folder not found');

        return KycFolderResponse::fromEntity($kycFolder, $kycFolder->workspace->name);
    }
}
