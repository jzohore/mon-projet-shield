<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Payment\Stripe;

use App\Application\User\UseCase\UpdateStripeCustomerIdUseCase;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Invoice;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;
use Stripe\Subscription;
use Webmozart\Assert\Assert;

readonly class StripeService
{
    public function __construct(
        private string $stripeSecretKey,
        private UpdateStripeCustomerIdUseCase $stripeCustomerIdUseCase,
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    /**
     * Enregistre un utilisateur en tant que "Customer" sur Stripe.
     */
    public function createStripeCustomer(User $user): string
    {
        try {
            Assert::notNull($user->email);
            Assert::notNull($user->getFullName());
            Stripe::setApiKey($this->stripeSecretKey);
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->getFullName(), // Ou le nom du Workspace selon ton architecture
                'metadata' => [
                    'user_id' => (string) $user->id, // Indispensable pour retrouver tes petits
                ],
            ]);

            Assert::notNull($customer->id);
            ($this->stripeCustomerIdUseCase)($user, $customer->id);

            return $customer->id;
        } catch (ApiErrorException $e) {
            // Gérer l'erreur proprement pour ne pas faire planter ton app
            throw new \RuntimeException('Impossible d\'enregistrer le client sur Stripe : ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function createSubscription(string $stripeCustomerId): string
    {
        try {
            Stripe::setApiKey($this->stripeSecretKey);

            $product = $this->productRepository->getByReference('plan_cabinet');
            Assert::notNull($product, 'Le produit "plan_cabinet" est introuvable.');

            // 🛡️ Type Narrowing : On extrait l'ID strict pour PHPStan
            $priceRaw = $product->stripePriceId;
            $priceId = $priceRaw;
            Assert::stringNotEmpty($priceId, 'L\'ID du prix Stripe est invalide ou manquant.');

            $stripeSubscription = Subscription::create([
                'customer' => $stripeCustomerId,
                'items' => [
                    ['price' => $priceId], // 🪄 On passe la chaîne stricte validée
                ],
                'trial_period_days' => 30, // 🪄 Les fameux 30 jours
                'trial_settings' => [
                    'end_behavior' => [
                        // 💡 TRÈS IMPORTANT : Dit à Stripe d'annuler l'abonnement
                        // si le client n'a pas ajouté de carte au 30ème jour
                        'missing_payment_method' => 'cancel',
                    ],
                ],
            ]);

            // 🛡️ On s'assure que Stripe a bien renvoyé un ID
            Assert::stringNotEmpty($stripeSubscription->id, 'Stripe n\'a pas retourné d\'ID d\'abonnement valide.');

            return $stripeSubscription->id;
        } catch (ApiErrorException $e) {
            // Gérer l'erreur proprement pour ne pas faire planter ton app
            throw new \RuntimeException('Impossible d\'enregistrer le nouvel abo sur Stripe : ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getSubscription(string $subscriptionId): Subscription
    {
        try {
            Stripe::setApiKey($this->stripeSecretKey);

            return Subscription::retrieve($subscriptionId);
        } catch (ApiErrorException $e) {
            // Gérer l'erreur proprement pour ne pas faire planter ton app
            throw new \RuntimeException('Impossible de recupérer l\'abonnement sur Stripe : ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return array<int, array{date: \DateTimeImmutable, amount: float|int, status: string|null, pdf_url: string|null}>
     */
    public function getInvoicesBySub(string $subscriptionId): array
    {
        try {
            Stripe::setApiKey($this->stripeSecretKey);
            $invoices = Invoice::all([
                'subscription' => $subscriptionId,
                'limit' => 12,
            ]);

            $invoiceHistory = [];

            foreach ($invoices->data as $invoice) {
                // On ignore les factures à 0€ (comme celles des périodes d'essai gratuites)
                if (0 === $invoice->amount_paid) {
                    continue;
                }

                $invoiceHistory[] = [
                    'date' => new \DateTimeImmutable()->setTimestamp($invoice->created),
                    'amount' => $invoice->amount_paid / 100, // Le montant RÉEL payé ce mois-là
                    'status' => $invoice->status, // ex: 'paid', 'open', 'void'
                    'pdf_url' => $invoice->invoice_pdf, // Le lien direct vers le PDF hébergé par Stripe !
                ];
            }

            return $invoiceHistory;
        } catch (ApiErrorException $e) {
            // Gérer l'erreur proprement pour ne pas faire planter ton app
            throw new \RuntimeException('Impossible de recupérer l\'abonnement sur Stripe : ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function cancelSubscription(string $subscriptionId, string $reason): Subscription
    {
        try {
            Stripe::setApiKey($this->stripeSecretKey);

            return Subscription::update($subscriptionId, [
                'cancel_at_period_end' => true,
                'metadata' => ['cancel_reason' => $reason],
            ]);
        } catch (ApiErrorException $e) {
            // Gérer l'erreur proprement pour ne pas faire planter ton app
            throw new \RuntimeException('Impossible de cancel l\'abonnement sur Stripe : ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function createProduct(int $priceInCents, bool $isRecurring, string $name, string $description, string $reference): string|Price|null
    {
        try {
            Stripe::setApiKey($this->stripeSecretKey);
            // 2. Préparation du prix pour Stripe
            $stripePriceData = [
                'currency' => 'eur',
                'unit_amount' => $priceInCents,
            ];

            // 🚀 Si c'est un abonnement, on ajoute l'intervalle mensuel !
            if ($isRecurring) {
                $stripePriceData['recurring'] = [
                    'interval' => 'month',
                ];
            }

            $stripeProduct = Product::create([
                'name' => $name,
                'description' => $description,
                'metadata' => [
                    'internal_reference' => $reference,
                ],
                'default_price_data' => $stripePriceData,
            ]);

            // 4. On récupère le fameux ID généré par Stripe (ex: price_12345...)
            return $stripeProduct->default_price;
        } catch (ApiErrorException $e) {
            // Gérer l'erreur proprement pour ne pas faire planter ton app
            throw new \RuntimeException('Impossible de cancel l\'abonnement sur Stripe : ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
