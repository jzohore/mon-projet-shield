<?php

namespace App\Application\Billing\UseCase\Subscription;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;

readonly class CreateSubscriptionUseCase
{
    public function __construct(
        private SubscriptionRepositoryInterface $repository,
        private string $kysureCabinetPriceId,
    ) {}

    public function __invoke(Workspace $workspace, string $stripeSubscriptionId): void
    {
        $subscription = Subscription::startCabinetTrial(
            workspace: $workspace,
            stripeSubscriptionId: $stripeSubscriptionId,
            stripePriceId: $this->kysureCabinetPriceId
        );

        $this->repository->save($subscription);
    }
}
