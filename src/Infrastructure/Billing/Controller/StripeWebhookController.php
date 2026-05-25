<?php

namespace App\Infrastructure\Billing\Controller;

use App\Application\Billing\UseCase\Credits\AddCreditsUseCase;
use App\Application\Billing\UseCase\Subscription\ActivateSubscriptionUseCase;
use App\Application\Billing\UseCase\Subscription\SyncSubscriptionUseCase;
use App\Application\Billing\UseCase\Subscription\TerminateSubscriptionUseCase;
use App\Domain\Billing\Enum\CreditAction;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
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
    ) {}

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        Assert::notNull($sigHeader);

        try {
            // 1. Vérification de la signature cryptographique de Stripe
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->stripeWebhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('Stripe Webhook : Payload invalide.');
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $this->logger->error('Stripe Webhook : Signature invalide (Tentative de fraude ?).');
            return new Response('Invalid signature', 400);
        }

        // =========================================================================
        // ÉVÉNEMENT 1 : PAIEMENT RÉUSSI (Achat de crédits ou Début d'abonnement)
        // =========================================================================
        if ($event->type === 'checkout.session.completed') {
            /** @var Session $session */
            $session = $event->data->object;
            $mode = $session->mode;

            $userIdString = $session->metadata->user_id ?? null;
            $userEmail = $session->metadata->user_email ?? null;
            $workspaceIdString = $session->metadata->workspace_id ?? null;

            if ($mode === 'setup' && ($session->metadata->purpose ?? null) === 'activate_existing_subscription') {
                $stripeSubscriptionId = $session->metadata->stripe_subscription_id ?? null;

                if ($workspaceIdString && $stripeSubscriptionId && $session->setup_intent) {
                    try {
                        $stripe = new StripeClient($this->stripeSecretKey);

                        $setupIntent = $stripe->setupIntents->retrieve($session->setup_intent);
                        $paymentMethodId = $setupIntent->payment_method;

                        if (!$paymentMethodId) {
                            throw new \RuntimeException('Aucun moyen de paiement trouvé sur le SetupIntent.');
                        }

                        $subscription = $stripe->subscriptions->retrieve($stripeSubscriptionId);

                        // 3. 🛡️ On dit au CUSTOMER : "Ceci est ta carte par défaut pour toutes tes futures factures"
                        $stripe->customers->update($subscription->customer, [
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
                        // 'active' = Payé avec succès.
                        // 'trialing' = Cas rare si Stripe a un délai.
                        if (in_array($updatedSubscription->status, ['active', 'trialing'])) {

                            $workspaceUuid = Uuid::fromString($workspaceIdString);
                            $userUuid = Uuid::fromString($userIdString);
                            ($this->activateSubscriptionUseCase)($workspaceUuid, $stripeSubscriptionId, $userEmail, $userUuid);

                        } else {
                            // Le paiement a échoué (fonds insuffisants, etc.)
                            // Le statut est 'past_due' ou 'incomplete'.
                            $this->logger->warning("Mise à jour abo: Carte refusée pour le workspace {$workspaceIdString}. Statut: {$updatedSubscription->status}");

                            // Optionnel : Tu pourrais avoir un UseCase pour marquer l'abo en INCOMPLETE en base de données
                        }

                    } catch (\Exception $e) {
                        $this->logger->critical('Erreur activation abonnement existant : ' . $e->getMessage());
                        return new Response('Erreur interne', 500);
                    }
                }

                return new Response('Webhook handled', 200);
            }

            // CAS 1 : PAIEMENT PONCTUEL (Achat de Crédits pour les Indés)
            if ($mode === 'payment') {
                $creditsToAdd = (int) ($session->metadata->credits_to_add ?? 0);
                $invoiceUrl = null;

                if (!empty($session->invoice)) {
                    try {
                        $stripe = new StripeClient($this->stripeSecretKey);
                        $invoice = $stripe->invoices->retrieve($session->invoice);
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
                        return new Response('Erreur interne', 500);
                    }
                }
            }
            return new Response('Webhook handled', 200);
        } elseif ($event->type === 'customer.subscription.deleted') {
            // Pour cet événement, l'objet n'est pas une Session, mais directement la Subscription Stripe
            /** @var Subscription $stripeSubscription */
            $stripeSubscription = $event->data->object;
            $stripeSubscriptionId = $stripeSubscription->id; // ex: sub_123456

            try {
                // 🚀 C'est ici qu'on coupe vraiment l'accès (Status -> CANCELED)
                ($this->terminateSubscriptionUseCase)($stripeSubscriptionId);

            } catch (\Exception $e) {
                $this->logger->critical('Erreur lors de la suppression de l\'abonnement (Webhook) : ' . $e->getMessage());
                return new Response('Erreur interne', 500);
            }

            return new Response('Webhook handled', 200);
        } elseif ($event->type === 'customer.subscription.updated') {
            /** @var Subscription $stripeSubscription */
            $stripeSubscription = $event->data->object;

            try {
                // On passe directement l'objet Stripe complet à notre Use Case
                // pour qu'il mette à jour notre base de données locale
                ($this->syncSubscriptionUseCase)($stripeSubscription);

            } catch (\Exception $e) {
                $this->logger->critical('Erreur synchronisation abonnement : ' . $e->getMessage());
                return new Response('Erreur interne', 500);
            }

            return new Response('Webhook handled', 200);
        }

        // On répond toujours 200 OK à Stripe rapidement pour les événements qu'on n'écoute pas
        return new Response('Webhook handled', 200);
    }
}
