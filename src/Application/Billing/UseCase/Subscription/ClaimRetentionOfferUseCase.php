<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Subscription;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Event\RetentionOfferClaimedEvent;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Service\Payment\Stripe\StripeService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Applique l'offre de fidélité (-30 % pendant 3 mois) proposée au moment d'une
 * tentative de résiliation. Utilisable une seule fois par abonnement.
 */
readonly class ClaimRetentionOfferUseCase
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
        if (!$subscription instanceof Subscription || !$subscription->isValid() || null === $subscription->stripeSubscriptionId) {
            throw new \DomainException('Aucun abonnement actif.');
        }

        if (!$subscription->canClaimRetentionOffer()) {
            throw new \DomainException('L\'offre de fidélité a déjà été utilisée.');
        }

        $this->stripeService->applyRetentionCoupon($subscription->stripeSubscriptionId);

        // Si une résiliation était programmée, on l'annule : le client reste.
        $subscription->claimRetentionOffer();
        $this->subscriptionRepository->save($subscription);

        $this->eventDispatcher->dispatch(new RetentionOfferClaimedEvent($subscription, $user, $workspace));
    }
}
