<?php

namespace App\Infrastructure\Billing\Controller;

use App\Application\Billing\UseCase\AddCreditsUseCase;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;
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
    ) {}

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        Assert::notNull($sigHeader);
        try {
            // 1. Vérification de la signature cryptographique de Stripe
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->stripeWebhookSecret
            );
        } catch (UnexpectedValueException $e) {
            $this->logger->error('Stripe Webhook : Payload invalide.');
            return new Response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            $this->logger->error('Stripe Webhook : Signature invalide (Tentative de fraude ?).');
            return new Response('Invalid signature', 400);
        }

        // 2. On traite uniquement l'événement de paiement réussi
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $userIdString = $session->metadata->user_id ?? null;
            $creditsToAdd = (int) ($session->metadata->credits_to_add ?? 0);

            // 1. On initialise l'URL à null
            $invoiceUrl = null;

            // 2. Si une facture a été générée, on va la chercher
            if (!empty($session->invoice)) {
                try {
                    // On utilise le SDK pour récupérer l'objet Invoice complet
                    $stripe = new \Stripe\StripeClient($this->stripeSecretKey);
                    $invoice = $stripe->invoices->retrieve($session->invoice);

                    // C'est ici que se cache l'URL publique de la facture
                    $invoiceUrl = $invoice->hosted_invoice_url;
                } catch (\Exception $e) {
                    $this->logger->error('Impossible de récupérer la facture Stripe : ' . $e->getMessage());
                    // On continue quand même, le client doit avoir ses crédits même sans facture
                }
            }

            if ($userIdString && $creditsToAdd > 0) {
                try {
                    $userUuid = Uuid::fromString($userIdString);

                    // 3. On passe maintenant l'URL de la facture à ton UseCase
                    ($this->addCreditsUseCase)(
                        $userUuid,
                        $creditsToAdd,
                        $invoiceUrl
                    );

                    return new Response('Webhook handled', 200);
                } catch (\Exception $e) {
                    $this->logger->critical('Erreur lors de l\'ajout des crédits : ' . $e->getMessage());
                    return new Response('Erreur interne', 500);
                }
            }
        }

        // On répond toujours 200 OK à Stripe rapidement pour couper la connexion
        return new Response('Webhook handled', 200);
    }
}
