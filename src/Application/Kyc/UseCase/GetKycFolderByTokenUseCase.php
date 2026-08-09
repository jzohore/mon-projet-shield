<?php

declare(strict_types=1);

namespace App\Application\Kyc\UseCase;

use App\Application\Kyc\DTO\Response\KycFolderResponse;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class GetKycFolderByTokenUseCase
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
    ) {
    }

    public function __invoke(string $shareToken): KycFolderResponse
    {
        Assert::notNull($shareToken);
        $kycFolder = $this->kycFolderRepository->findByToken(shareToken: $shareToken);
        Assert::notNull($kycFolder, 'Kyc folder not found');

        return KycFolderResponse::fromEntity($kycFolder);
    }
}
