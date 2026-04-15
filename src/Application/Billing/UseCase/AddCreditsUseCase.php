<?php

namespace App\Application\Billing\UseCase;

use App\Domain\Billing\Event\CreditPurchasedEvent;
use App\Domain\Wallet\Enum\TransactionType;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

readonly class AddCreditsUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(Uuid $userId, int $creditsToAdd, ?string $invoiceUrl = null): void
    {
        $workspaceMember = $this->workspaceMemberRepository->findOneByUser($userId);
        Assert::notNull($workspaceMember, "L'utilisateur n'est membre d'aucun espace de travail.");
        $user = $workspaceMember->user;
        Assert::notNull($user, "L'utilisateur est introuvable.");
        $workspace = $workspaceMember->workspace;

        Assert::notNull($workspace, "L'espace de travail est introuvable.");

        $transaction = $workspace->credit($creditsToAdd, TransactionType::STRIPE_PURCHASE->value, $invoiceUrl);
        Assert::notNull($transaction, "La transaction est introuvable.");
        // On sauvegarde en base
        $this->workspaceRepository->save($workspace);

        $this->eventDispatcher->dispatch(new CreditPurchasedEvent(
            $workspace,
            $transaction,
            $user,
            $invoiceUrl,
        ));
    }
}
