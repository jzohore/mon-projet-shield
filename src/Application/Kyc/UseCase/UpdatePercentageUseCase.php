<?php

namespace App\Application\Kyc\UseCase;

use App\Domain\Kyc\Event\UpdatePercentageStakeholderEvent;
use App\Domain\Kyc\Repository\StakeholderRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class UpdatePercentageUseCase
{
    public function __construct(
        private StakeholderRepositoryInterface $stakeholderRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(string $slugId, int $percentage): void
    {
        $currentStakeHolder = $this->stakeholderRepository->findBySlugId($slugId);
        Assert::notnull($currentStakeHolder, 'Le stakeholder n\'existe pas');
        $currentKycFolder = $currentStakeHolder->folder;
        Assert::notNull($currentKycFolder);
        $currentStakeHolder->updatePercentage($percentage);

        $this->stakeholderRepository->save($currentStakeHolder);

        $this->eventDispatcher->dispatch(new UpdatePercentageStakeholderEvent($currentKycFolder, $currentStakeHolder));
    }
}
