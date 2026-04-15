<?php

namespace App\Infrastructure\User\Twig\Components;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'MembersListComponent',
    template: 'components/User/MembersListComponent.html.twig'
)]
class MembersListComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: false)]
    public ?string $query = null;

    #[LiveProp(writable: true)]
    public Uuid $userSlugId;

    #[LiveProp(writable: true, url: false)]
    public ?bool $enabled = null;

    #[LiveProp(writable: true, url: false)]
    public int $page = 1;

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
        private readonly WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private readonly GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
    ) {}

    /**
     * @return Pagerfanta<User>
     */
    public function getMembers(): Pagerfanta
    {
        $workspace = ($this->getCurrentWorkspaceInfo)($this->userSlugId);
        $workspaceSlugId = $workspace->slugId;
        Assert::notNull($workspaceSlugId);
        /** @var Pagerfanta<User> $members */
        $members = $this->workspaceMemberRepository->getMembersList($workspaceSlugId, $this->query);

        $members->setMaxPerPage(10);
        $members->setCurrentPage($this->page);

        return $members;
    }
}
