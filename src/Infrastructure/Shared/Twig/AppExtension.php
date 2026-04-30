<?php

namespace App\Infrastructure\Shared\Twig;

use App\Application\Billing\DTO\Response\SubscriptionInfoResponse;
use App\Application\Billing\UseCase\Subscription\GetCurrentSubscriptionUseCase;
use App\Application\Workspace\DTO\Response\WorkspaceInfoResponse;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly CurrentWorkspaceProvider $currentWorkspaceProvider,
        private readonly GetCurrentSubscriptionUseCase $currentSubscriptionUseCase,
    ) {}
    public function getFilters(): array
    {
        return [
            new TwigFilter('siret', $this->formatSiret(...)),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('workspaceInfo', $this->workspaceInfo(...)),
            new TwigFunction('subInfo', $this->subInfo(...)),
        ];
    }

    public function formatSiret(string $siret): string
    {
        // On nettoie les espaces éventuels et on formate : 3 3 3 5
        $siret = str_replace(' ', '', $siret);
        if (strlen($siret) !== 14) {
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
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        return WorkspaceInfoResponse::fromEntity($workspace);
    }

    public function subInfo(): SubscriptionInfoResponse
    {
        return ($this->currentSubscriptionUseCase)();
    }
}
