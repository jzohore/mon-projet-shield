<?php

namespace App\Infrastructure\Service\Payment\Stripe;

use App\Application\Billing\DTO\Response\ProductResponse;
use App\Domain\User\Entity\User;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Webmozart\Assert\Assert;

readonly class StripeCheckoutService
{
    public function __construct(
        private string $stripeSecretKey,
    ) {}

    public function createSessionUrl(
        User $user,
        ProductResponse $product,
        string $successUrl,
        string $cancelUrl
    ): string {
        Stripe::setApiKey($this->stripeSecretKey);
        Assert::notNull($user->email);
        $session = Session::create([
            'customer_email' => $user->email, // Pré-remplit l'email sur la page Stripe
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $product->stripePriceId,
                'quantity' => 1,
            ]],
            'mode' => 'payment', // 'payment' = paiement ponctuel (pas d'abonnement)
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'invoice_creation' => [
                'enabled' => true,
            ],
            'metadata' => [
                'user_id' => (string) $user->id,
                'product_id' => (string) $product->slugId,
                'credits_to_add' => (string) $product->credits,
            ],
        ]);

        Assert::notNull($session->url);

        return $session->url;
    }
}
