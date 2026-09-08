<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Twig;

use App\Application\Billing\DTO\Response\SubscriptionInfoResponse;
use App\Application\Billing\UseCase\Subscription\GetCurrentSubscriptionUseCase;
use App\Application\Workspace\DTO\Response\WorkspaceInfoResponse;
use App\Application\Workspace\UseCase\CurrentWorkspaceInfo;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Domain\Workspace\Service\SeatAvailability;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly GetCurrentSubscriptionUseCase $currentSubscriptionUseCase,
        private readonly CurrentWorkspaceInfo $currentWorkspaceInfo,
        private readonly SeatAvailability $seatAvailability,
        private readonly CurrentWorkspaceProvider $currentWorkspaceProvider,
    ) {
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('siret', $this->formatSiret(...)),
        ];
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('workspaceInfo', $this->workspaceInfo(...)),
            new TwigFunction('subInfo', $this->subInfo(...)),
            new TwigFunction('seatInfo', $this->seatInfo(...)),
        ];
    }

    /**
     * @return array{used: int, allowed: int, remaining: int, isFirm: bool}
     */
    public function seatInfo(): array
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();

        return [
            'used' => $this->seatAvailability->usedSeats($workspace),
            'allowed' => $this->seatAvailability->allowedSeats($workspace),
            'remaining' => $this->seatAvailability->remainingSeats($workspace),
            'isFirm' => $workspace->isFirm(),
        ];
    }

    public function formatSiret(string $siret): string
    {
        // On nettoie les espaces éventuels et on formate : 3 3 3 5
        $siret = str_replace(' ', '', $siret);
        if (14 !== strlen($siret)) {
            return $siret;
        }

        return sprintf(
            '%s %s %s %s',
            substr($siret, 0, 3),
            substr($siret, 3, 3),
            substr($siret, 6, 3),
            substr($siret, 9, 5)
        );
    }

    public function workspaceInfo(): WorkspaceInfoResponse
    {
        return ($this->currentWorkspaceInfo)();
    }

    public function subInfo(): SubscriptionInfoResponse
    {
        return ($this->currentSubscriptionUseCase)();
    }
}
