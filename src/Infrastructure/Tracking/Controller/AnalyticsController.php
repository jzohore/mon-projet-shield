<?php

namespace App\Infrastructure\Tracking\Controller;

use App\Infrastructure\UI\Analytics\ClickAnalyticsChartBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/admin/analytics', name: 'admin_analytics_show', methods: ['GET', 'POST'])]
#[IsGranted("ROLE_SUPER_ADMIN")]
readonly class AnalyticsController
{
    public function __construct(
        private Environment $twig,
    ) {}

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(ClickAnalyticsChartBuilder $chartBuilder): Response
    {
        return new Response(
            $this->twig->render('@admin/analytics/analytics_show.html.twig', [
                'page_title' => 'Dashboard Analytique',
                'elementsChart' => $chartBuilder->buildTopElementsChart(5),
                'trendChart'    => $chartBuilder->buildTrendChart(7),
            ])
        );
    }
}
