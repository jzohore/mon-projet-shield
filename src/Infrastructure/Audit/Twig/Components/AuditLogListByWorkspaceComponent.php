<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit\Twig\Components;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Pagerfanta\Pagerfanta;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'AuditLogListByWorkspaceComponent',
    template: 'components/Audit/AuditLogListByWorkspaceComponent.html.twig'
)]
class AuditLogListByWorkspaceComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: false)]
    public ?string $query = null;

    #[LiveProp(writable: true, url: false)]
    public int $page = 1;

    #[LiveProp(writable: true, url: false)]
    public ?AuditEventType $statusFilter = null;

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
        private readonly AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @return Pagerfanta<AuditLog>
     */
    public function getItems(): Pagerfanta
    {
        $workspace = $this->workspaceProvider->getWorkspace();
        $kyc = $this->auditLogRepository->getAuditLogsList($workspace, $this->statusFilter, $this->query);
        $kyc->setMaxPerPage(10);
        $kyc->setCurrentPage($this->page);

        return $kyc;
    }

    /**
     * @return array<AuditEventType>
     */
    public function getStatuses(): array
    {
        return array_filter(
            AuditEventType::cases(),
            static fn (AuditEventType $type): bool => $type->isVisibleToWorkspace()
        );
    }
}
