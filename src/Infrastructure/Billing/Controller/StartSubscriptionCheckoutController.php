<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Controller;

use App\Domain\Billing\Enum\Plan;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Billing\Service\StripePriceResolver;
use App\Infrastructure\Service\Payment\Stripe\StripeCheckoutService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/app/billing/subscribe', name: 'app_billing_subscribe', methods: ['POST'])]
#[IsCsrfTokenValid('billing_subscribe')]
final readonly class StartSubscriptionCheckoutController
{
    public function __construct(
        private StripeCheckoutService $checkoutService,
        private StripePriceResolver $priceResolver,
        private CurrentWorkspaceProvider $workspaceProvider,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
    ) {
    }

    public function __invoke(
        Request $request,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $plan = Plan::tryFrom((string) $request->request->get('plan'));
        $seats = max(1, (int) $request->request->get('seats', 1));

        if (null === $plan) {
            return $this->backToPricing('Offre inconnue.');
        }

        $workspace = $this->workspaceProvider->getWorkspace();

        try {
            $url = $this->checkoutService->createPlanSubscriptionUrl(
                user: $user,
                workspace: $workspace,
                plan: $plan,
                seats: $seats,
                priceId: $this->priceResolver->forPlan($plan),
                successUrl: $this->urlGenerator->generate('app_settings_subscription', [], UrlGeneratorInterface::ABSOLUTE_URL),
                cancelUrl: $this->urlGenerator->generate('app_pricing', [], UrlGeneratorInterface::ABSOLUTE_URL),
            );
        } catch (\Throwable $e) {
            $this->logger->error('Échec de création du checkout abonnement : ' . $e->getMessage());

            return $this->backToPricing('Le paiement n\'a pas pu être initié. Réessayez dans un instant.');
        }

        return new RedirectResponse($url, Response::HTTP_SEE_OTHER);
    }

    private function backToPricing(string $error): RedirectResponse
    {
        $session = $this->requestStack->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('error', $error);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_pricing'));
    }
}
