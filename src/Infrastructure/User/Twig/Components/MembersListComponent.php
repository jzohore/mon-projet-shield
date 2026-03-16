<?php

namespace App\Infrastructure\User\Twig\Components;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Pagerfanta\Pagerfanta;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
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

    #[LiveProp(writable: true, url: true)]
    public ?string $query = null;

    #[LiveProp(writable: true)]
    public string $workspaceId;

    #[LiveProp(writable: true, url: true)]
    public ?bool $enabled = null;

    #[LiveProp(writable: true, url: true)]
    public int $page = 1;
    public function __construct(
        public readonly UserRepositoryInterface $userRepository,
        public readonly WorkspaceRepositoryInterface $workspaceRepository,
    ) {}

    /**
     * @return Pagerfanta<User>
     */
    public function getMembers(): Pagerfanta
    {
        $workspace = $this->workspaceRepository->findOneBySlug($this->workspaceId);
        Assert::isInstanceOf($workspace, Workspace::class);
        /** @var Pagerfanta<User> $members */
        $members = $this->userRepository->findMembersForList($workspace, $this->query, $this->enabled);

        $members->setMaxPerPage(10);
        $members->setCurrentPage($this->page);

        return $members;
    }
}
