<?php

namespace App\Application\Kyc\UseCase;

use App\Application\Kyc\DTO\Request\StakeholderRequest;
use App\Domain\Kyc\Entity\Stakeholder;
use App\Domain\Kyc\Enum\StakeholderRole;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Domain\Kyc\Repository\StakeholderRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class CreateStakeHolderUseCase
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
        private StakeholderRepositoryInterface $stakeholderRepository,
    ) {}

    public function __invoke(StakeholderRequest $request): void
    {
        $currentKycFolder = $this->kycFolderRepository->findBySlugId($request->folderSlugId);
        Assert::notNull($currentKycFolder);

        foreach ($request->data as $apiDirigeant) {
            // 1. On utilise notre traducteur magique
            // Il va lire "Gérant" et te retourner StakeholderRole::LEGAL_REPRESENTATIVE
            $qualite = $apiDirigeant['qualite'] ?? 'gérant';
            $roleEnum = StakeholderRole::fromApiRole($qualite);

            // 2. Tu crées ton entité Stakeholder
            $stakeholder = Stakeholder::createBeneficialOwner(
                $currentKycFolder,
                $apiDirigeant['prenoms'],
                $apiDirigeant['nom'],
                $roleEnum,
            );
            $this->stakeholderRepository->save($stakeholder);
        }

    }
}
