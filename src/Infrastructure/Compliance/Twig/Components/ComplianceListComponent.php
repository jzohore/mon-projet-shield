<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Pagerfanta\Pagerfanta;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'ComplianceListComponent',
    template: 'components/Compliance/ComplianceListComponent.html.twig'
)]
class ComplianceListComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: false)]
    public ?string $query = null;

    #[LiveProp(writable: true, url: false)]
    public int $page = 1;

    #[LiveProp(writable: true, url: false)]
    public ?ComplianceFolderStatus $statusFilter = null;

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

    public function __construct(
        private readonly CurrentWorkspaceProvider $workspaceProvider,
        private readonly ComplianceFolderRepositoryInterface $complianceFolderRepository,
    ) {
    }

    /**
     * @return Pagerfanta<ComplianceFolder>
     */
    public function getItems(): Pagerfanta
    {
        $workspace = $this->workspaceProvider->getWorkspace();
        $kyc = $this->complianceFolderRepository->findAllByWorkspace($workspace, $this->query, $this->statusFilter);
        $kyc->setMaxPerPage(10);
        $kyc->setCurrentPage($this->page);

        return $kyc;
    }

    /**
     * @return array<ComplianceFolderStatus>
     */
    public function getStatuses(): array
    {
        return ComplianceFolderStatus::getKycPhaseStatuses();
    }
}
