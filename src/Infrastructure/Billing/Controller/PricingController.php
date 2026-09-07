<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Controller;

use App\Application\Billing\UseCase\Pricing\GetPricingUseCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/app/pricing', name: 'app_pricing', methods: ['GET'])]
final readonly class PricingController
{
    public function __construct(
        private Environment $twig,
        private GetPricingUseCase $getPricing,
    ) {
    }

    public function __invoke(): Response
    {
        return new Response(
            $this->twig->render('@app/billing/pricing.html.twig', [
                'page_title' => 'Offres & tarifs',
                'pricing' => ($this->getPricing)(),
            ]),
        );
    }
}
