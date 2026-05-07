<?php

namespace App\Infrastructure\Support\Twig\Components;

use App\Domain\Support\Entity\SupportThread;
use App\Domain\Support\Enum\SupportThreadStatus;
use App\Domain\Support\Repository\SupportThreadRepositoryInterface;
use Pagerfanta\Pagerfanta;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'AdminSupportListComponent',
    template: 'components/Support/AdminSupportListComponent.html.twig'
)]
class AdminSupportListComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: false)]
    public ?string $query = null;

    #[LiveProp(writable: true, url: false)]
    public int $page = 1;

    #[LiveProp(writable: true, url: false)]
    public ?SupportThreadStatus $statusFilter = null;

    #[LiveAction]
    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    #[LiveAction]
    public function nextPage(): void
    {
        $this->page++;
    }

    public function __construct(
        private readonly SupportThreadRepositoryInterface $supportThreadRepository,
    ) {}

    /**
     * @return Pagerfanta<SupportThread>
     */
    public function getSupportThreads(): Pagerfanta
    {
        $kyc = $this->supportThreadRepository->getPaginatedSupport($this->query, $this->statusFilter);
        $kyc->setMaxPerPage(10);
        $kyc->setCurrentPage($this->page);

        return $kyc;
    }

    /**
     * @return array<SupportThreadStatus>
     */
    public function getAvailableStatuses(): array
    {
        return SupportThreadStatus::cases();
    }
}
