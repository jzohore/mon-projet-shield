<?php

namespace App\Infrastructure\Billing\Twig;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Wallet\Entity\WalletTransaction;
use App\Domain\Wallet\Repository\WalletTransactionsRepositoryInterface;
use Pagerfanta\Pagerfanta;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'WalletTransactionsListComponent',
    template: 'components/Billing/WalletTransactionsListComponent.html.twig'
)]
class WalletTransactionsListComponent
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
        public readonly WalletTransactionsRepositoryInterface $walletTransactionsRepository,
        public readonly GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
        public readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @return Pagerfanta<WalletTransaction>
     */
    public function getTransactionsList(): Pagerfanta
    {
        $user = $this->userRepository->findBySlug($this->userSlugId);
        Assert::notNull($user);
        $userId = $user->id;
        Assert::notNull($userId, "L'utilisateur doit avoir un ID pour récupérer le workspace.");
        $workspace = ($this->getCurrentWorkspaceInfo)($userId);

        $workspaceSlugId = $workspace->slugId;
        Assert::notNull($workspaceSlugId);

        $kyc = $this->walletTransactionsRepository->getTransactionsList($workspaceSlugId);
        $kyc->setMaxPerPage(10);
        $kyc->setCurrentPage($this->page);

        return $kyc;
    }
}
