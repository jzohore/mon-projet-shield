<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Twig;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Wallet\Entity\WalletTransaction;
use App\Domain\Wallet\Repository\WalletTransactionsRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
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
        public readonly WalletTransactionsRepositoryInterface $walletTransactionsRepository,
        public readonly UserRepositoryInterface $userRepository,
        private readonly CurrentWorkspaceProvider $workspaceProvider,
    ) {
    }

    /**
     * @return Pagerfanta<WalletTransaction>
     */
    public function getTransactionsList(): Pagerfanta
    {
        $workspace = $this->workspaceProvider->getWorkspace();

        $workspaceSlugId = $workspace->slugId;
        Assert::notNull($workspaceSlugId);

        $kyc = $this->walletTransactionsRepository->getTransactionsList($workspaceSlugId);
        $kyc->setMaxPerPage(10);
        $kyc->setCurrentPage($this->page);

        return $kyc;
    }
}
