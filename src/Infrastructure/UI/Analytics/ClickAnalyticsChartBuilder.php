<?php

declare(strict_types=1);

namespace App\Infrastructure\UI\Analytics;

use App\Domain\Tracking\Repository\ClickLogRepositoryInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class ClickAnalyticsChartBuilder
{
    public function __construct(
        private ClickLogRepositoryInterface $analyticsRepository,
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    public function buildTopElementsChart(int $limit = 5): Chart
    {
        $topElements = $this->analyticsRepository->getTopClickedElements($limit);
        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

        $chart->setData([
            'labels' => array_map(static fn (\App\Application\Tracking\DTO\Request\ElementStatDTO $dto): string => $dto->elementName, $topElements),
            'datasets' => [
                [
                    'label' => 'Clics',
                    'backgroundColor' => ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                    'data' => array_map(static fn (\App\Application\Tracking\DTO\Request\ElementStatDTO $dto): int => $dto->clickCount, $topElements),
                ],
            ],
        ]);

        // Optionnel : On peut ajouter des options spécifiques Chart.js ici
        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
        ]);

        return $chart;
    }

    public function buildTrendChart(int $days = 7): Chart
    {
        $trends = $this->analyticsRepository->getClicksTrend($days);
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => array_keys($trends),
            'datasets' => [
                [
                    'label' => 'Volume de clics',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.2)',
                    'borderColor' => '#4f46e5',
                    'data' => array_values($trends),
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => ['beginAtZero' => true],
            ],
        ]);

        return $chart;
    }
}
