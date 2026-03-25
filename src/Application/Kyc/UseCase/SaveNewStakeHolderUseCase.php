<?php

namespace App\Application\Kyc\UseCase;

use App\Application\Kyc\DTO\Request\AddStakeholderRequest;
use App\Domain\Kyc\Entity\Stakeholder;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Domain\Kyc\Repository\StakeholderRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class SaveNewStakeHolderUseCase
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
        private StakeholderRepositoryInterface $stakeholderRepository,
    ) {}

    public function __invoke(AddStakeholderRequest $request): void
    {
        Assert::notNull($request->folderSlugId);
        Assert::notNull($request->firstName);
        Assert::notNull($request->lastName);
        Assert::notNull($request->role);
        Assert::notNull($request->percentage);
        $currentKycFolder = $this->kycFolderRepository->findBySlugId($request->folderSlugId);
        Assert::notNull($currentKycFolder);

        $stakeholder = Stakeholder::createBeneficialOwner(
            $currentKycFolder,
            $request->firstName,
            $request->lastName,
            $request->role,
            $request->percentage
        );
        $this->stakeholderRepository->save($stakeholder);

    }
}
