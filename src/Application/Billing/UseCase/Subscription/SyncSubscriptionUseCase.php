<?php

namespace App\Application\Billing\UseCase\Subscription;

use App\Domain\Billing\Enum\SubscriptionStatus;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use Stripe\Subscription;

readonly class SyncSubscriptionUseCase
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository
    ) {}

    public function __invoke(Subscription $stripeSubscription): void
    {
        // 1. On retrouve l'abonnement local grâce à l'ID Stripe (ex: sub_12345)
        $subscription = $this->subscriptionRepository->getByStripeId($stripeSubscription->id);

        // 🚀 BOUCLIER ANTI COLLISION (Race Condition)
        // On ignore la synchro si l'abonnement a été mis à jour il y a moins de 10 secondes par l'activation
        $now = new \DateTimeImmutable();
        $updatedAt = $subscription->updateAt ?? $subscription->createdAt;

        if ($updatedAt->diff($now)->s < 10 && $updatedAt->diff($now)->i === 0) {
            return;
        }

        // 🛡️ BOUCLIER ANTI-1970
        // Par défaut, on garde la date actuelle de notre base de données
        $newPeriodEnd = $subscription->currentPeriodEnd;

        $timestamp = $stripeSubscription->current_period_end ?? 0;
        if ($timestamp > 0) {
            // Si Stripe nous donne un timestamp valide, on l'utilise
            $parsedDate = \DateTimeImmutable::createFromFormat('U', (string) $timestamp);
            if ($parsedDate !== false) {
                $newPeriodEnd = $parsedDate;
            }
        }

        // Préparation du statut
        $statusEnum = SubscriptionStatus::tryFrom($stripeSubscription->status) ?? SubscriptionStatus::ACTIVE;

        // 2. On met à jour via ta méthode métier
        $subscription->syncSubscription(
            currentPeriodEnd: $newPeriodEnd, // Date sécurisée !
            status: $statusEnum,
            cancelPeriodEnd: $stripeSubscription->cancel_at_period_end,
        );

        // 3. On sauvegarde
        $this->subscriptionRepository->save($subscription);
    }
}
