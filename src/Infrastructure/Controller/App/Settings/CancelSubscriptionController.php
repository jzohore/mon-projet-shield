<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App\Settings;

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
    ) {
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(): Response
    {
        return new Response(
            $this->twig->render('@app/settings/subscription_cancel.html.twig', [
                'page_title' => 'Paramètres - Résilier mon abonnement',
                'sub_title' => 'Confirmez la résiliation de votre abonnement.',
                'subInfo' => ($this->currentSubscriptionUseCase)(),
            ])
        );
    }
}
