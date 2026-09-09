<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Subscription;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Event\SubscriptionResumedEvent;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Service\Payment\Stripe\StripeService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Lève la suspension : la facturation Stripe reprend et l'accès est rétabli.
 */
readonly class ResumeSubscriptionUseCase
{
    public function __construct(
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
        private CurrentUserProvider $currentUserProvider,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private StripeService $stripeService,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(): void
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        $user = $this->currentUserProvider->getUser();

        if (!$this->workspaceMemberRepository->isUserAdminOfWorkspace($user, $workspace)) {
            throw NotWorkspaceAdminException::create();
        }

        $subscription = $workspace->subscription;
        if (!$subscription instanceof Subscription || null === $subscription->stripeSubscriptionId) {
            throw new \DomainException('Aucun abonnement à reprendre.');
        }

        if (!$subscription->isPaused()) {
            return;
        }

        $this->stripeService->resumeSubscription($subscription->stripeSubscriptionId);
        $subscription->resume();
        $this->subscriptionRepository->save($subscription);

        $this->eventDispatcher->dispatch(new SubscriptionResumedEvent($subscription, $user, $workspace));
    }
}
