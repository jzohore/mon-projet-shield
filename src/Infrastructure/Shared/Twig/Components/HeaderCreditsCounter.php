<?php

namespace App\Infrastructure\Shared\Twig\Components;

use App\Application\Workspace\DTO\Response\WorkspaceInfoResponse;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
    name: 'HeaderCreditsCounter',
    template: 'components/HeaderCreditsCounter.html.twig'
)]
final readonly class HeaderCreditsCounter
{
    public function __construct(
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
    ) {}

    public function getCreditCount(): WorkspaceInfoResponse
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        return WorkspaceInfoResponse::fromEntity($workspace);
    }
}
