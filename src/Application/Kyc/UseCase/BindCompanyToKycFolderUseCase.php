<?php

declare(strict_types=1);

namespace App\Application\Kyc\UseCase;

use App\Application\Kyc\DTO\Request\BindCompanyToKycFolderRequest;
use App\Domain\Kyc\Event\BindCompanyEvent;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class BindCompanyToKycFolderUseCase
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(BindCompanyToKycFolderRequest $request): void
    {
        Assert::notNull($request->folderSlugId);
        Assert::notNull($request->companyName);
        Assert::notNull($request->companySiret);
        Assert::notNull($request->companySiren);
        Assert::notNull($request->companyAddress);
        Assert::notNull($request->statusAdministratif);
        Assert::notNull($request->companyLegalCategory);

        $currentKycFolder = $this->kycFolderRepository->findBySlugId($request->folderSlugId);
        Assert::notNull($currentKycFolder);

        $currentKycFolder->bindCompany(
            companyName: $request->companyName,
            siret: $request->companySiret,
            siren: $request->companySiren,
            address: $request->companyAddress,
            statusAdministratif: $request->statusAdministratif,
            legalCategory: $request->companyLegalCategory,
        );

        $this->kycFolderRepository->save($currentKycFolder);
        $this->eventDispatcher->dispatch(new BindCompanyEvent($currentKycFolder));
    }
}
