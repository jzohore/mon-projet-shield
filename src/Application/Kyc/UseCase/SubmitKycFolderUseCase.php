<?php

declare(strict_types=1);

namespace App\Application\Kyc\UseCase;

use App\Domain\Kyc\Event\KycFolderSubmittedEvent;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class SubmitKycFolderUseCase
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $folderSlugId, bool $isCertified): void
    {
        $currentKycFolder = $this->kycFolderRepository->findBySlugId($folderSlugId);
        Assert::notNull($currentKycFolder);
        $currentKycFolder->submitForReview($isCertified);

        $this->kycFolderRepository->save($currentKycFolder);
        $this->eventDispatcher->dispatch(new KycFolderSubmittedEvent($currentKycFolder));
    }
}
