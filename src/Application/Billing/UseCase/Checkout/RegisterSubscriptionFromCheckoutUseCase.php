<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Checkout;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Enum\Plan;
use App\Domain\Billing\Enum\SubscriptionStatus;
use App\Domain\Billing\Event\SubscriptionActivatedEvent;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Enregistre (ou active) l'abonnement KYSURE d'un workspace après confirmation
 * d'un Stripe Checkout. Appelé par le webhook `checkout.session.completed`
 * (purpose = kysure_subscription).
 *
 * Idempotent : si un abonnement porte déjà ce `stripeSubscriptionId`, on ne fait
 * rien. Les dates de période exactes sont recalées ensuite par le webhook
 * `customer.subscription.updated` (SyncSubscriptionUseCase).
 */
readonly class RegisterSubscriptionFromCheckoutUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private UserRepositoryInterface $userRepository,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        string $workspaceId,
        string $userId,
        string $stripeSubscriptionId,
        string $stripePriceId,
        string $planReference,
        int $seats,
        string $recipientEmail,
    ): void {
        if ($this->subscriptionRepository->findByStripeId($stripeSubscriptionId) instanceof Subscription) {
            $this->logger->info('Abonnement déjà enregistré, webhook ignoré.', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);

            return;
        }

        $plan = Plan::tryFrom($planReference) ?? Plan::INDIVIDUAL;
        $workspace = $this->workspaceRepository->getById(Uuid::fromString($workspaceId));
        $user = $this->userRepository->getById(Uuid::fromString($userId));

        $now = new \DateTimeImmutable();
        $existing = $workspace->subscription;

        if ($existing instanceof Subscription) {
            // Le workspace avait déjà une ligne d'abonnement (essai, tentative
            // précédente) : on la rattache au nouvel abonnement Stripe.
            $existing->activateSubscription($stripeSubscriptionId);
            $existing->updateSeats(max($plan->getMinSeats(), $seats));
            $subscription = $existing;
        } else {
            $subscription = Subscription::forPlan(
                workspace: $workspace,
                stripeSubscriptionId: $stripeSubscriptionId,
                stripePriceId: $stripePriceId,
                plan: $plan,
                seatsCount: $seats,
                status: SubscriptionStatus::ACTIVE,
                currentPeriodStart: $now,
                currentPeriodEnd: $now->modify('+1 month'),
            );
        }

        $this->transactionManager->transactional(function () use ($subscription): void {
            $this->subscriptionRepository->save($subscription);
        });

        $this->eventDispatcher->dispatch(new SubscriptionActivatedEvent(
            workspace: $workspace,
            recipientEmail: $recipientEmail,
            subscription: $subscription,
            user: $user,
        ));
    }
}
