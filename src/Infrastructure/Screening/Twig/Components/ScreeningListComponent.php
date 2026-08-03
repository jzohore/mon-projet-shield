<?php

declare(strict_types=1);

namespace App\Infrastructure\Screening\Twig\Components;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'ScreeningListComponent',
    template: 'components/Screening/ScreeningListComponent.html.twig'
)]
class ScreeningListComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: false)]
    public ?string $query = null;

    #[LiveProp(writable: true, url: false)]
    public int $page = 1;

    #[LiveAction]
    public function previousPage(): void
    {
        if ($this->page > 1) {
            --$this->page;
        }
    }

    #[LiveAction]
    public function nextPage(): void
    {
        ++$this->page;
    }

    #[LiveProp]
    public Uuid $userSlugId;

    public function __construct(
        private readonly GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
        private readonly ScreeningAuditRepositoryInterface $screeningAuditRepository,
    ) {
    }

    /**
     * @return Pagerfanta<ScreeningAudit>
     */
    public function getScreeningList(): Pagerfanta
    {
        $workspace = ($this->getCurrentWorkspaceInfo)($this->userSlugId);

        $workspaceSlugId = $workspace->slugId;
        Assert::notNull($workspaceSlugId);

        $kyc = $this->screeningAuditRepository->getScreeningList($workspaceSlugId, $this->query);
        $kyc->setMaxPerPage(10);
        $kyc->setCurrentPage($this->page);

        return $kyc;
    }
}
