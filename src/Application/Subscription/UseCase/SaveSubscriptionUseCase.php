<?php

namespace App\Application\Subscription\UseCase;

use App\Application\Subscription\DTO\SubscriptionRequest;
use App\Domain\Subscription\Entity\Subscription;
use App\Domain\Subscription\Repository\SubscriptionRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class SaveSubscriptionUseCase
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
    ) {}

    public function __invoke(SubscriptionRequest $request): void
    {
        Assert::notNull($request->workspaceSlugId);
        Assert::notNull($request->plan);
        Assert::notNull($request->expiresAt);

        $workspace = $this->workspaceRepository->findOneBySlug($request->workspaceSlugId);
        Assert::isInstanceOf($workspace, Workspace::class);

        $sub = Subscription::create(
            $workspace,
            $request->plan,
            $request->expiresAt,
        );

        $this->subscriptionRepository->save($sub);
    }
}
