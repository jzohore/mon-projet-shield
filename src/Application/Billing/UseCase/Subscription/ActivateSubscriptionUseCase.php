<?php

namespace App\Application\Billing\UseCase\Subscription;

use App\Domain\Billing\Event\SubscriptionActivatedEvent;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

readonly class ActivateSubscriptionUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(Uuid $workspaceId, string $stripeSubscriptionId, string $recipientEmail): void
    {
        // 1. On retrouve le cabinet qui vient de payer
        $workspace = $this->workspaceRepository->getById($workspaceId);

        // 2. On récupère la souscription actuelle, ou on la crée si c'est son premier paiement
        $subscription = $workspace->subscription;
        Assert::notNull($subscription);
        $subscription->activateSubscription($stripeSubscriptionId);
        // 5. On sauvegarde en base de données
        $this->subscriptionRepository->save($subscription);
        $this->eventDispatcher->dispatch(new SubscriptionActivatedEvent($workspace, $recipientEmail, $subscription));
    }
}
