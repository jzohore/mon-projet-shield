<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App\Settings;

use App\Application\Billing\UseCase\Products\GetEnterpriseProductUseCase;
use App\Application\Billing\UseCase\Subscription\GetCurrentSubscriptionUseCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/settings/subscription/cancel', name: 'app_settings_subscription_cancel')]
readonly class CancelSubscriptionController
{
    public function __construct(
        private Environment $twig,
        private GetCurrentSubscriptionUseCase $currentSubscriptionUseCase,
        private GetEnterpriseProductUseCase $getEnterpriseProductUseCase,
    ) {
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(): Response
    {
        $subscription = ($this->currentSubscriptionUseCase)();
        $product = ($this->getEnterpriseProductUseCase)();

        return new Response(
            $this->twig->render('@app/settings/subscription_cancel.html.twig', [
                'page_title' => 'Paramètres - Gérer mon abonnement',
                'sub_title' => 'Consultez votre forfait actuel, votre consommation et vos factures.',
                'subInfo' => $subscription,
                'product' => $product,
            ])
        );
    }
}
