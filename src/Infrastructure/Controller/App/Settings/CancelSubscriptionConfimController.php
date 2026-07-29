<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App\Settings;

use App\Application\Billing\UseCase\Subscription\CancelPendingSubscriptionUseCase;
use App\Application\Billing\UseCase\Subscription\GetCurrentSubscriptionUseCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Webmozart\Assert\Assert;

#[AsController]
#[Route(path: '/app/settings/subscription/cancel-confirm', name: 'app_settings_subscription_cancel_confirm', methods: ['POST'])]
#[IsCsrfTokenValid('cancel-subscription')]
readonly class CancelSubscriptionConfimController
{
    public function __construct(
        private GetCurrentSubscriptionUseCase $currentSubscriptionUseCase,
        private UrlGeneratorInterface $urlGenerator,
        private CancelPendingSubscriptionUseCase $cancelSubscriptionUseCase,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $reason = $request->request->get('reason');
        Assert::string($reason);
        $subscription = ($this->currentSubscriptionUseCase)();
        if ('active' === $subscription->status) {
            try {
                // 3. On lance ton Use Case
                ($this->cancelSubscriptionUseCase)($reason);
                // $this->addFlash('success', 'Votre demande de résiliation a bien été prise en compte.');
            } catch (\Exception) {
                // $this->addFlash('error', 'Une erreur est survenue lors de la résiliation.');
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('app_settings_subscription'));
    }
}
