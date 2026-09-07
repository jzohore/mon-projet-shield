<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Checkout;

use App\Domain\Billing\Event\MinutePackPurchasedEvent;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Crédite un workspace en minutes d'entretien après paiement Stripe d'un pack.
 * Appelé par le webhook `checkout.session.completed` (purpose = kysure_minute_pack).
 *
 * L'idempotence repose sur le garde `ProcessedStripeEvent` en amont (webhook) :
 * un même événement Stripe rejoué n'atteint jamais ce use case deux fois.
 */
readonly class GrantMinutePackFromCheckoutUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        string $workspaceId,
        int $minutes,
        string $recipientEmail,
        ?string $invoiceUrl = null,
    ): void {
        if ($minutes <= 0) {
            $this->logger->warning('Pack de minutes ignoré : quantité nulle ou négative.', [
                'workspace_id' => $workspaceId,
                'minutes' => $minutes,
            ]);

            return;
        }

        $workspace = $this->workspaceRepository->getById(Uuid::fromString($workspaceId));

        $this->transactionManager->transactional(function () use ($workspace, $minutes): void {
            $workspace->grantMeetingMinutes($minutes);
            $this->workspaceRepository->save($workspace);
        });

        $this->eventDispatcher->dispatch(new MinutePackPurchasedEvent(
            workspaceSlugId: $workspace->slugId,
            minutesGranted: $minutes,
            remainingMeetingMinutes: $workspace->remainingMeetingMinutes(),
            recipientEmail: $recipientEmail,
            workspaceName: $workspace->name,
            invoiceUrl: $invoiceUrl,
        ));
    }
}
