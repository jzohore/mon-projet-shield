<?php

namespace App\Infrastructure\Shared\Twig\Components;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
    name: 'HeaderCreditsCounter',
    template: 'components/HeaderCreditsCounter.html.twig'
)]
final readonly class HeaderCreditsCounter
{
    public function __construct(
        private GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
    ) {}

    public function getCreditCount(Uuid $userId): int
    {
        return ($this->getCurrentWorkspaceInfo)($userId)->balance;
    }
}
