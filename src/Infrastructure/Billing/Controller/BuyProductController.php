<?php

namespace App\Infrastructure\Billing\Controller;

use App\Application\Billing\UseCase\Products\GetProductUseCase;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
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
        private CurrentWorkspaceProvider $workspaceProvider,
    ) {}

    public function __invoke(
        string $id,
        #[CurrentUser]
        User
        $user,
    ): Response {
        $product = ($this->getProductUseCase)($id);
        Assert::notNull($product);
        $workspace = $this->workspaceProvider->getWorkspace();
        $isFirm = $workspace->isFirm();
        $successRoute = $isFirm ? 'app_settings_subscription' : 'app_settings_billing';
        $cancelRoute = $isFirm ? 'app_settings_subscription' : 'app_billing_products';
        $successUrl = $this->urlGenerator->generate(
            $successRoute,
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $cancelUrl = $this->urlGenerator->generate(
            $cancelRoute, // On le renvoie sur la grille des prix s'il annule
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $workspace = $this->workspaceProvider->getWorkspace();
        // 2. Création de la session Stripe
        if ($isFirm && $workspace->subscription !== null && $workspace->subscription->stripeSubscriptionId !== null) {
            $checkoutUrl = $this->stripeCheckoutService->createSetupSessionUrl(
                $user,
                $workspace,
                $successUrl,
                $cancelUrl
            );
        } else {
            $checkoutUrl = $this->stripeCheckoutService->createSessionUrl(
                $user,
                $product,
                $workspace,
                $successUrl,
                $cancelUrl
            );
        }

        // 3. Redirection 303 (See Other) recommandée par Stripe
        return new RedirectResponse($checkoutUrl, 303);
    }
}
