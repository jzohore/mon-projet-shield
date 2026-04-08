<?php

namespace App\Infrastructure\Shared\Twig\Components;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Webmozart\Assert\Assert;

#[AsTwigComponent(
    name: 'SidebarKycCounter',
    template: 'components/SidebarKycCounter.html.twig'
)]
final readonly class SidebarKycCounter
{
    public function __construct(
        private GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
        private KycFolderRepositoryInterface $kycRepository,
    ) {}

    public function getCount(Uuid $userId): int
    {
        $workspace = ($this->getCurrentWorkspaceInfo)($userId);
        Assert::notNull($workspace->slugId);
        return $this->kycRepository->countDraftsForWorkspace($workspace->slugId);
    }
}
