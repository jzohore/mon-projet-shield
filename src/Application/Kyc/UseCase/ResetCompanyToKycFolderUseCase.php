<?php

declare(strict_types=1);

namespace App\Application\Kyc\UseCase;

use App\Domain\Kyc\Event\CompanyResetEvent;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class ResetCompanyToKycFolderUseCase
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $folderSlugId): void
    {
        $currentKycFolder = $this->kycFolderRepository->findBySlugId($folderSlugId);
        Assert::notNull($currentKycFolder);
        $oldDocuments = $currentKycFolder->documents->toArray();
        $currentKycFolder->removeCompany();
        $this->kycFolderRepository->save($currentKycFolder);

        $this->eventDispatcher->dispatch(new CompanyResetEvent($currentKycFolder, $oldDocuments));
    }
}
