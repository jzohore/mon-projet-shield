<?php

declare(strict_types=1);

namespace App\Infrastructure\KYC\Twig\Components;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\Kyc\Entity\KycFolder;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use Pagerfanta\Pagerfanta;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'KycFolderListComponent',
    template: 'components/Kyc/KycFolderListComponent.html.twig'
)]
class KycFolderListComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public ?string $query = null;

    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    #[LiveProp(writable: true, url: true)]
    public ?string $status = null;

    #[LiveProp]
    public ?string $userSlugId = null;

    public function __construct(
        public readonly KycFolderRepositoryInterface $kycFolderRepository,
        public readonly GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
        public readonly UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @return Pagerfanta<KycFolder>
     */
    public function getKycList(): Pagerfanta
    {
        $user = $this->userRepository->findBySlug($this->userSlugId);
        Assert::notNull($user);
        $userId = $user->id;
        Assert::notNull($userId, "L'utilisateur doit avoir un ID pour récupérer le workspace.");
        $workspace = ($this->getCurrentWorkspaceInfo)($userId);

        $workspaceSlugId = $workspace->slugId;
        Assert::notNull($workspaceSlugId);

        $kyc = $this->kycFolderRepository->getKycFolderList($workspaceSlugId, $this->query);
        $kyc->setMaxPerPage(10);
        $kyc->setCurrentPage($this->page);

        return $kyc;
    }
}
