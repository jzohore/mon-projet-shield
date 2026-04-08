<?php

namespace App\Infrastructure\Billing\Controller;

use App\Application\Billing\UseCase\GetProductUseCase;
use App\Domain\User\Entity\User;
use App\Infrastructure\Service\Payment\Stripe\StripeCheckoutService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Webmozart\Assert\Assert;

#[AsController]
#[Route(path: '/app/billing/buy/{id}', name: 'app_billing_buy', methods: ['GET'])]
readonly class BuyProductController
{
    public function __construct(
        private StripeCheckoutService $stripeCheckoutService,
        private UrlGeneratorInterface $urlGenerator,
        private GetProductUseCase $getProductUseCase,
    ) {}

    public function __invoke(
        string $id,
        #[CurrentUser]
        User
        $user,
    ): Response {
        $product = ($this->getProductUseCase)($id);
        Assert::notNull($product);
        $successUrl = $this->urlGenerator->generate(
            'app_settings_billing',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $cancelUrl = $this->urlGenerator->generate(
            'app_billing_products', // On le renvoie sur la grille des prix s'il annule
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // 2. Création de la session Stripe
        $checkoutUrl = $this->stripeCheckoutService->createSessionUrl(
            $user,
            $product,
            $successUrl,
            $cancelUrl
        );

        // 3. Redirection 303 (See Other) recommandée par Stripe
        return new RedirectResponse($checkoutUrl, 303);
    }
}
