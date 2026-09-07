<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Payment\Stripe;

use App\Domain\Billing\Enum\MinutePack;
use App\Domain\Billing\Enum\Plan;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Webmozart\Assert\Assert;

readonly class StripeCheckoutService
{
    public function __construct(
        private string $stripeSecretKey,
    ) {
    }

    /**
     * Abonnement KYSURE « au siège » : une ligne d'abonnement dont la quantité
     * est le nombre de sièges. Le workspace devra déjà avoir un client Stripe.
     */
    public function createPlanSubscriptionUrl(
        User $user,
        Workspace $workspace,
        Plan $plan,
        int $seats,
        string $priceId,
        string $successUrl,
        string $cancelUrl,
    ): string {
        Stripe::setApiKey($this->stripeSecretKey);

        Assert::notNull($user->email);
        Assert::notNull($user->id);
        Assert::notNull($workspace->id);
        Assert::stringNotEmpty($priceId, 'Identifiant de prix Stripe manquant pour l\'abonnement.');

        $params = [
            'mode' => 'subscription',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => max($plan->getMinSeats(), $seats),
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $workspace->id,
            'metadata' => [
                'purpose' => 'kysure_subscription',
                'workspace_id' => (string) $workspace->id,
                'user_id' => (string) $user->id,
                'user_email' => $user->email,
                'plan' => $plan->value,
                'seats' => (string) max($plan->getMinSeats(), $seats),
            ],
        ];

        if (null !== $user->profile->stripeCustomerId) {
            $params['customer'] = $user->profile->stripeCustomerId;
        } else {
            $params['customer_email'] = $user->email;
        }

        $session = Session::create($params);
        Assert::notNull($session->url);

        return $session->url;
    }

    /**
     * Pack de minutes d'entretien prépayées : paiement ponctuel, facture générée.
     */
    public function createMinutePackUrl(
        User $user,
        Workspace $workspace,
        MinutePack $pack,
        string $priceId,
        string $successUrl,
        string $cancelUrl,
    ): string {
        Stripe::setApiKey($this->stripeSecretKey);

        Assert::notNull($user->email);
        Assert::notNull($user->id);
        Assert::notNull($workspace->id);
        Assert::stringNotEmpty($priceId, 'Identifiant de prix Stripe manquant pour le pack de minutes.');

        $params = [
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'invoice_creation' => ['enabled' => true],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $workspace->id,
            'metadata' => [
                'purpose' => 'kysure_minute_pack',
                'workspace_id' => (string) $workspace->id,
                'user_id' => (string) $user->id,
                'user_email' => $user->email,
                'pack' => $pack->value,
                'minutes' => (string) $pack->getMinutes(),
            ],
        ];

        if (null !== $user->profile->stripeCustomerId) {
            $params['customer'] = $user->profile->stripeCustomerId;
        } else {
            $params['customer_email'] = $user->email;
        }

        $session = Session::create($params);
        Assert::notNull($session->url);

        return $session->url;
    }
}
