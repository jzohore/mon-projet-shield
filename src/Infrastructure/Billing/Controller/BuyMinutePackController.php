<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Controller;

use App\Domain\Billing\Enum\MinutePack;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Billing\Service\StripePriceResolver;
use App\Infrastructure\Service\Payment\Stripe\StripeCheckoutService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/app/billing/minutes', name: 'app_billing_minutes', methods: ['POST'])]
#[IsCsrfTokenValid('billing_minutes')]
final readonly class BuyMinutePackController
{
    public function __construct(
        private StripeCheckoutService $checkoutService,
        private StripePriceResolver $priceResolver,
        private CurrentWorkspaceProvider $workspaceProvider,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        Request $request,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $pack = MinutePack::tryFrom((string) $request->request->get('pack'));

        if (null === $pack) {
            return new RedirectResponse($this->urlGenerator->generate('app_pricing'));
        }

        $workspace = $this->workspaceProvider->getWorkspace();

        try {
            $url = $this->checkoutService->createMinutePackUrl(
                user: $user,
                workspace: $workspace,
                pack: $pack,
                priceId: $this->priceResolver->forMinutePack($pack),
                successUrl: $this->urlGenerator->generate('app_settings_billing', [], UrlGeneratorInterface::ABSOLUTE_URL),
                cancelUrl: $this->urlGenerator->generate('app_pricing', [], UrlGeneratorInterface::ABSOLUTE_URL),
            );
        } catch (\Throwable $e) {
            $this->logger->error('Échec de création du checkout pack de minutes : ' . $e->getMessage());

            return new RedirectResponse($this->urlGenerator->generate('app_pricing'));
        }

        return new RedirectResponse($url, Response::HTTP_SEE_OTHER);
    }
}
