<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components;

use App\Domain\User\Entity\Client;
use App\Domain\User\Entity\ClientWorkspaceRelation;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Pagerfanta\Pagerfanta;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'WorkspaceClientListComponent',
    template: 'components/Compliance/WorkspaceClientListComponent.html.twig',
)]
class WorkspaceClientListComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: false)]
    public ?string $query = null;

    #[LiveProp(writable: true, url: false)]
    public int $page = 1;

    /** 'recent' | 'name' | 'status' */
    #[LiveProp(writable: true, url: false)]
    public string $sort = 'recent';

    /** '' (tous) | 'active' | 'inactive' */
    #[LiveProp(writable: true, url: false)]
    public string $statusFilter = '';

    public function __construct(
        private readonly CurrentWorkspaceProvider $workspaceProvider,
        private readonly ClientRepositoryInterface $clientRepository,
    ) {
    }

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

    /**
     * @return Pagerfanta<Client>
     */
    public function getItems(): Pagerfanta
    {
        $sort = match ($this->sort) {
            'name' => 'name',
            'status' => 'status',
            default => 'recent',
        };

        $onlyActive = match ($this->statusFilter) {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        $items = $this->clientRepository->findAllByWorkspace(
            $this->workspaceProvider->getWorkspace(),
            $this->query,
            $sort,
            $onlyActive,
        );
        $items->setMaxPerPage(10);
        $items->setCurrentPage(max(1, $this->page));

        return $items;
    }

    /** @var array<string, int>|null */
    private ?array $folderCounts = null;

    public function folderCountFor(Client $client): int
    {
        if (null === $this->folderCounts) {
            $ids = [];
            foreach ($this->getItems() as $item) {
                $ids[] = (string) $item->id;
            }
            $this->folderCounts = $this->clientRepository->folderCountByClients(
                $this->workspaceProvider->getWorkspace(),
                $ids,
            );
        }

        return $this->folderCounts[(string) $client->id] ?? 0;
    }

    /** 'active' | 'pending' | 'inactive' — état de la relation pour CE cabinet. */
    public function relationStatusFor(Client $client): string
    {
        $relation = $client->relationWith($this->workspaceProvider->getWorkspace());

        return match (true) {
            !$relation instanceof ClientWorkspaceRelation => 'inactive',
            $relation->isPending() => 'pending',
            $relation->isActive() => 'active',
            default => 'inactive',
        };
    }
}
