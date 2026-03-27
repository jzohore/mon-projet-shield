<?php

namespace App\Application\Kyc\UseCase;

use App\Domain\Kyc\Event\RemoveStakeholderEvent;
use App\Domain\Kyc\Repository\StakeholderRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class RemoveStakeHolderUseCase
{
    public function __construct(
        private StakeholderRepositoryInterface $stakeholderRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(string $slugId): void
    {
        $currentStakeHolder = $this->stakeholderRepository->findBySlugId($slugId);
        Assert::notnull($currentStakeHolder, 'Le stakeholder n\'existe pas');
        $currentKycFolder = $currentStakeHolder->folder;
        Assert::notNull($currentKycFolder);
        $stakeholderName = $currentStakeHolder->firstName . ' ' . $currentStakeHolder->lastName;
        $this->stakeholderRepository->remove($currentStakeHolder);

        $this->eventDispatcher->dispatch(new RemoveStakeHolderEvent($currentKycFolder, $stakeholderName));
    }
}
