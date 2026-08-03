<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Credits;

use App\Domain\Billing\Enum\CreditAction;
use App\Domain\Billing\Event\CreditPurchasedEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

readonly class AddCreditsUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private EventDispatcherInterface $eventDispatcher,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(Uuid $workspaceId, Uuid $userId, int $creditsToAdd, CreditAction $creditAction, ?string $invoiceUrl = null): void
    {
        $workspace = $this->workspaceRepository->getById($workspaceId);
        $user = $this->userRepository->getById($userId);
        $transaction = $workspace->credit($creditsToAdd, $creditAction->value, $invoiceUrl);
        $this->workspaceRepository->save($workspace);

        $this->eventDispatcher->dispatch(new CreditPurchasedEvent(
            $user,
            $workspace,
            $transaction,
            $invoiceUrl,
        ));
    }
}
