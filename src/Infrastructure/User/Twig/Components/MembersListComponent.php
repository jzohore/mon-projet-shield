<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Twig\Components;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Pagerfanta\Pagerfanta;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'MembersListComponent',
    template: 'components/User/MembersListComponent.html.twig'
)]
class MembersListComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: false)]
    public ?string $query = null;

    #[LiveProp(writable: true, url: false)]
    public ?bool $enabled = null;

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

    public function __construct(
        private readonly WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private readonly CurrentWorkspaceProvider $currentWorkspaceProvider,
    ) {
    }

    /**
     * @return Pagerfanta<User>
     */
    public function getMembers(): Pagerfanta
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();

        /** @var Pagerfanta<User> $members */
        $members = $this->workspaceMemberRepository->getMembersList($workspace, $this->query);

        $members->setMaxPerPage(10);
        $members->setCurrentPage($this->page);

        return $members;
    }
}
