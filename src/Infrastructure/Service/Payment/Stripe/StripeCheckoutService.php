<?php

namespace App\Infrastructure\Service\Payment\Stripe;

use App\Application\Billing\DTO\Response\ProductResponse;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Webmozart\Assert\Assert;

readonly class StripeCheckoutService
{
    public function __construct(
        private string $stripeSecretKey,
    ) {}

    public function createSetupSessionUrl(
        User $user,
        Workspace $workspace,
        string $successUrl,
        string $cancelUrl
    ): string {
        Stripe::setApiKey($this->stripeSecretKey);

        Assert::notNull($user->email);
        Assert::notNull($user->profile->stripeCustomerId);
        Assert::notNull($workspace->subscription);
        Assert::notNull($workspace->subscription->stripeSubscriptionId);

        $session = Session::create([
            'customer' => $user->profile->stripeCustomerId,
            'payment_method_types' => ['card'],
            'mode' => 'setup',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'user_id' => (string) $user->id,
                'user_email' => (string) $user->email,
                'workspace_id' => (string) $workspace->id,
                'stripe_subscription_id' => $workspace->subscription->stripeSubscriptionId,
                'purpose' => 'activate_existing_subscription',
            ],
        ]);

        Assert::notNull($session->url);

        return $session->url;
    }

    public function createSessionUrl(
        User $user,
        ProductResponse $product,
        Workspace $workspace,
        string $successUrl,
        string $cancelUrl
    ): string {
        Stripe::setApiKey($this->stripeSecretKey);
        Assert::notNull($user->email);
        $isFirm = $workspace->isFirm();
        $sessionParams = [
            'customer_email' => $user->email,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $product->stripePriceId,
                'quantity' => 1,
            ]],
            'mode' => $isFirm ? 'subscription' : 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'user_id' => (string) $user->id,
                'user_email' => (string) $user->email,
                'workspace_id' => (string) $workspace->id,
                'product_id' => (string) $product->slugId,
                'credits_to_add' => (string) $product->credits,
            ],
        ];

        if (!$isFirm) {
            $sessionParams['invoice_creation'] = [
                'enabled' => true,
            ];
        }

        $session = Session::create($sessionParams);

        Assert::notNull($session->url);

        return $session->url;
    }
}
