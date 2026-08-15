<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Controller;

use App\Application\Billing\UseCase\Credits\AddCreditsUseCase;
use App\Application\Billing\UseCase\Subscription\ActivateSubscriptionUseCase;
use App\Application\Billing\UseCase\Subscription\SyncSubscriptionUseCase;
use App\Application\Billing\UseCase\Subscription\TerminateSubscriptionUseCase;
use App\Domain\Billing\Enum\CreditAction;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\StripeClient;
use Stripe\Subscription;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AsController]
#[Route(path: '/api/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
readonly class StripeWebhookController
{
    public function __construct(
        private AddCreditsUseCase $addCreditsUseCase,
        private LoggerInterface $logger,
        private string $stripeWebhookSecret,
        private string $stripeSecretKey,
        private ActivateSubscriptionUseCase $activateSubscriptionUseCase,
        private TerminateSubscriptionUseCase $terminateSubscriptionUseCase,
        private SyncSubscriptionUseCase $syncSubscriptionUseCase,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        Assert::notNull($sigHeader, 'Header Stripe-Signature manquant.');

        try {
            // 1. Vérification de la signature cryptographique de Stripe
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->stripeWebhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('Stripe Webhook : Payload invalide.');

            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $this->logger->error('Stripe Webhook : Signature invalide (Tentative de fraude ?).');

            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }
        // =========================================================================
        // ÉVÉNEMENT 1 : PAIEMENT RÉUSSI (Achat de crédits ou Début d'abonnement)
        // =========================================================================
        if ('checkout.session.completed' === $event->type) {
            /** @var Session $session */
            $session = $event->data->object;
            $mode = $session->mode;
            $userIdString = $session->metadata->user_id ?? null;
            $userEmail = $session->metadata->user_email ?? null;
            $workspaceIdString = $session->metadata->workspace_id ?? null;
            if ('setup' === $mode && ($session->metadata->purpose ?? null) === 'activate_existing_subscription') {
                $stripeSubscriptionId = $session->metadata->stripe_subscription_id ?? null;

                if ($workspaceIdString && $stripeSubscriptionId && $session->setup_intent) {
                    try {
                        $stripe = new StripeClient($this->stripeSecretKey);

                        // 🛡️ Extraction de l'ID string de manière sécurisée
                        $setupIntentRaw = $session->setup_intent;
                        $setupIntentId = is_string($setupIntentRaw) ? $setupIntentRaw : $setupIntentRaw->id;
                        Assert::stringNotEmpty($setupIntentId, 'ID du SetupIntent invalide.');

                        $setupIntent = $stripe->setupIntents->retrieve($setupIntentId);

                        // 🛡️ Extraction du PaymentMethod ID string
                        $paymentMethodRaw = $setupIntent->payment_method;
                        $paymentMethodId = is_string($paymentMethodRaw) ? $paymentMethodRaw : $paymentMethodRaw?->id;
                        Assert::stringNotEmpty($paymentMethodId, 'Aucun moyen de paiement trouvé sur le SetupIntent.');

                        $subscription = $stripe->subscriptions->retrieve($stripeSubscriptionId);

                        // 🛡️ Extraction du Customer ID string
                        $customerRaw = $subscription->customer;
                        $customerId = match (true) {
                            is_string($customerRaw) => $customerRaw,
                            default => null,
                        };
                        Assert::stringNotEmpty($customerId, 'ID Client Stripe invalide.');

                        // 3. On dit au CUSTOMER : "Ceci est ta carte par défaut pour toutes tes futures factures"
                        $stripe->customers->update($customerId, [
                            'invoice_settings' => [
                                'default_payment_method' => $paymentMethodId,
                            ],
                        ]);

                        // 1. On stocke le retour de Stripe dans une variable
                        $updatedSubscription = $stripe->subscriptions->update($stripeSubscriptionId, [
                            'default_payment_method' => $paymentMethodId,
                            'trial_end' => 'now', // Déclenche la facturation immédiate
                            'cancel_at_period_end' => false,
                        ]);

                        // 🚀 2. LA SÉCURITÉ : On vérifie si le prélèvement a bien fonctionné !
                        if (in_array($updatedSubscription->status, ['active', 'trialing'], true)) {
                            $workspaceUuid = Uuid::fromString($workspaceIdString);
                            $userUuid = Uuid::fromString($userIdString);
                            ($this->activateSubscriptionUseCase)($workspaceUuid, $stripeSubscriptionId, $userEmail, $userUuid);
                        } else {
                            $this->logger->warning("Mise à jour abo: Carte refusée pour le workspace {$workspaceIdString}. Statut: {$updatedSubscription->status}");
                        }
                    } catch (\Exception $e) {
                        $this->logger->critical('Erreur activation abonnement existant : ' . $e->getMessage());

                        return new Response('Erreur interne', Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }

                return new Response('Webhook handled', Response::HTTP_OK);
            }
            // CAS 1 : PAIEMENT PONCTUEL (Achat de Crédits pour les Indés)
            if ('payment' === $mode) {
                $creditsToAdd = (int) ($session->metadata->credits_to_add ?? 0);
                $invoiceUrl = null;

                if (!empty($session->invoice)) {
                    try {
                        $stripe = new StripeClient($this->stripeSecretKey);

                        // 🛡️ Extraction sécurisée de l'ID facture
                        $invoiceRaw = $session->invoice;
                        $invoiceId = is_string($invoiceRaw) ? $invoiceRaw : $invoiceRaw->id;
                        Assert::stringNotEmpty($invoiceId, 'ID Facture Stripe invalide.');

                        $invoice = $stripe->invoices->retrieve($invoiceId);
                        $invoiceUrl = $invoice->hosted_invoice_url;
                    } catch (\Exception $e) {
                        $this->logger->error('Impossible de récupérer la facture Stripe : ' . $e->getMessage());
                    }
                }

                if ($userIdString && $creditsToAdd > 0) {
                    try {
                        $workspaceUuid = Uuid::fromString($workspaceIdString);
                        $userUuid = Uuid::fromString($userIdString);
                        ($this->addCreditsUseCase)($workspaceUuid, $userUuid, $creditsToAdd, CreditAction::STRIPE_PURCHASE, $invoiceUrl);
                    } catch (\Exception $e) {
                        $this->logger->critical('Erreur crédits : ' . $e->getMessage());

                        return new Response('Erreur interne', Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }
            }

            return new Response('Webhook handled', Response::HTTP_OK);
        }
        if ('customer.subscription.deleted' === $event->type) {
            /** @var Subscription $stripeSubscription */
            $stripeSubscription = $event->data->object;
            $stripeSubscriptionId = $stripeSubscription->id;
            try {
                ($this->terminateSubscriptionUseCase)($stripeSubscriptionId);
            } catch (\Exception $e) {
                $this->logger->critical('Erreur lors de la suppression de l\'abonnement (Webhook) : ' . $e->getMessage());

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
}
