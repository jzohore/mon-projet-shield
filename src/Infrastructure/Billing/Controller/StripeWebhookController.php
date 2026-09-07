<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Controller;

use App\Application\Billing\Service\StripeWebhookIdempotency;
use App\Application\Billing\UseCase\Checkout\GrantMinutePackFromCheckoutUseCase;
use App\Application\Billing\UseCase\Checkout\RegisterSubscriptionFromCheckoutUseCase;
use App\Application\Billing\UseCase\Subscription\SyncSubscriptionUseCase;
use App\Application\Billing\UseCase\Subscription\TerminateSubscriptionUseCase;
use App\Domain\Billing\Enum\Plan;
use App\Infrastructure\Billing\Service\StripePriceResolver;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Stripe\Subscription;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Webmozart\Assert\Assert;

#[AsController]
#[Route(path: '/api/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
readonly class StripeWebhookController
{
    public function __construct(
        private LoggerInterface $logger,
        private string $stripeWebhookSecret,
        private string $stripeSecretKey,
        private TerminateSubscriptionUseCase $terminateSubscriptionUseCase,
        private SyncSubscriptionUseCase $syncSubscriptionUseCase,
        private StripeWebhookIdempotency $idempotency,
        private RegisterSubscriptionFromCheckoutUseCase $registerSubscriptionFromCheckout,
        private GrantMinutePackFromCheckoutUseCase $grantMinutePackFromCheckout,
        private StripePriceResolver $priceResolver,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        Assert::notNull($sigHeader, 'Header Stripe-Signature manquant.');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $this->stripeWebhookSecret);
        } catch (\UnexpectedValueException) {
            $this->logger->error('Stripe Webhook : Payload invalide.');

            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException) {
            $this->logger->error('Stripe Webhook : Signature invalide (tentative de fraude ?).');

            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        // 🛡️ Idempotence : Stripe peut rejouer un événement (retries, incidents).
        // On ne marque « traité » qu'en cas de réponse 2xx (sinon Stripe rejouera).
        if ($this->idempotency->isAlreadyProcessed($event->id)) {
            return new Response('Already processed', Response::HTTP_OK);
        }

        $response = $this->handleEvent($event);

        if ($response->isSuccessful()) {
            $this->idempotency->markProcessed($event->id);
        }

        return $response;
    }

    private function handleEvent(\Stripe\Event $event): Response
    {
        if ('checkout.session.completed' === $event->type) {
            /** @var Session $session */
            $session = $event->data->object;
            $userIdString = $session->metadata->user_id ?? null;
            $userEmail = $session->metadata->user_email ?? null;
            $workspaceIdString = $session->metadata->workspace_id ?? null;
            $purpose = $session->metadata->purpose ?? null;

            // --- Abonnement KYSURE « au siège » ---
            if ('kysure_subscription' === $purpose && $workspaceIdString && $userIdString && $userEmail) {
                $subscriptionRaw = $session->subscription;
                $stripeSubscriptionId = is_string($subscriptionRaw) ? $subscriptionRaw : $subscriptionRaw?->id;
                $planReference = (string) ($session->metadata->plan ?? Plan::INDIVIDUAL->value);
                $seats = (int) ($session->metadata->seats ?? 1);

                if ($stripeSubscriptionId) {
                    try {
                        $plan = Plan::tryFrom($planReference) ?? Plan::INDIVIDUAL;
                        ($this->registerSubscriptionFromCheckout)(
                            workspaceId: $workspaceIdString,
                            userId: $userIdString,
                            stripeSubscriptionId: $stripeSubscriptionId,
                            stripePriceId: $this->priceResolver->forPlan($plan),
                            planReference: $planReference,
                            seats: $seats,
                            recipientEmail: $userEmail,
                        );
                    } catch (\Exception $e) {
                        $this->logger->critical('Erreur enregistrement abonnement (checkout) : ' . $e->getMessage());

                        return new Response('Erreur interne', Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }

                return new Response('Webhook handled', Response::HTTP_OK);
            }

            // --- Pack de minutes d'entretien prépayées ---
            if ('kysure_minute_pack' === $purpose && $workspaceIdString && $userEmail) {
                $minutes = (int) ($session->metadata->minutes ?? 0);

                try {
                    ($this->grantMinutePackFromCheckout)(
                        workspaceId: $workspaceIdString,
                        minutes: $minutes,
                        recipientEmail: $userEmail,
                        invoiceUrl: $this->resolveInvoiceUrl($session),
                    );
                } catch (\Exception $e) {
                    $this->logger->critical('Erreur crédit pack de minutes (checkout) : ' . $e->getMessage());

                    return new Response('Erreur interne', Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                return new Response('Webhook handled', Response::HTTP_OK);
            }

            return new Response('Webhook handled', Response::HTTP_OK);
        }

        if ('customer.subscription.deleted' === $event->type) {
            /** @var Subscription $stripeSubscription */
            $stripeSubscription = $event->data->object;
            try {
                ($this->terminateSubscriptionUseCase)($stripeSubscription->id);
            } catch (\Exception $e) {
                $this->logger->critical('Erreur résiliation abonnement (webhook) : ' . $e->getMessage());

                return new Response('Erreur interne', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return new Response('Webhook handled', Response::HTTP_OK);
        }

        if ('customer.subscription.updated' === $event->type) {
            /** @var Subscription $stripeSubscription */
            $stripeSubscription = $event->data->object;
            try {
                ($this->syncSubscriptionUseCase)($stripeSubscription);
            } catch (\Exception $e) {
                $this->logger->critical('Erreur synchronisation abonnement : ' . $e->getMessage());

                return new Response('Erreur interne', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return new Response('Webhook handled', Response::HTTP_OK);
        }

        return new Response('Webhook handled', Response::HTTP_OK);
    }

    private function resolveInvoiceUrl(Session $session): ?string
    {
        if (empty($session->invoice)) {
            return null;
        }

        try {
            $stripe = new StripeClient($this->stripeSecretKey);
            $invoiceRaw = $session->invoice;
            $invoiceId = is_string($invoiceRaw) ? $invoiceRaw : $invoiceRaw->id;
            Assert::stringNotEmpty($invoiceId, 'ID Facture Stripe invalide.');

            return $stripe->invoices->retrieve($invoiceId)->hosted_invoice_url;
        } catch (\Exception $e) {
            $this->logger->error('Impossible de récupérer la facture Stripe : ' . $e->getMessage());

            return null;
        }
    }
}
