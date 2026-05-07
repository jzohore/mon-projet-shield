<?php

namespace App\Application\Billing\UseCase\Subscription;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use Webmozart\Assert\Assert;

readonly class CreateSubscriptionUseCase
{
    public function __construct(
        private SubscriptionRepositoryInterface $repository,
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(Workspace $workspace, string $stripeSubscriptionId): void
    {
        $stripeProductPrideId = $this->productRepository->getByReference('plan_cabinet');
        Assert::notNull($stripeProductPrideId);
        $subscription = Subscription::startCabinetTrial(
            workspace: $workspace,
            stripeSubscriptionId: $stripeSubscriptionId,
            stripePriceId: $stripeProductPrideId->stripePriceId,
        );

        $this->repository->save($subscription);
    }
}
