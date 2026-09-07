<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Listener\Checkout;

use App\Domain\Billing\Event\MinutePackPurchasedEvent;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Infrastructure\Workspace\Message\SendWorkspaceQuotaGrantedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Confirmation d'achat d'un pack de minutes : on réutilise l'e-mail « quota
 * rechargé » (Phase 1) avec 0 dossier et N minutes.
 */
#[AsEventListener(event: MinutePackPurchasedEvent::class)]
readonly class SendMinutePackPurchasedEmailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(MinutePackPurchasedEvent $event): void
    {
        if ('' === trim($event->recipientEmail)) {
            $this->logger->warning('Achat de pack de minutes : e-mail destinataire absent, notification ignorée.', [
                'workspace_slug_id' => $event->workspaceSlugId,
            ]);

            return;
        }

        $workspace = $this->workspaceRepository->findOneBySlug($event->workspaceSlugId);
        $trialRemaining = $workspace instanceof Workspace ? $workspace->trialDossiersRemaining : 0;

        $this->messageBus->dispatch(new SendWorkspaceQuotaGrantedMessage(
            recipientEmail: $event->recipientEmail,
            workspaceName: $event->workspaceName,
            dossiersGranted: 0,
            minutesGranted: $event->minutesGranted,
            trialDossiersRemaining: $trialRemaining,
            remainingMinutes: $event->remainingMeetingMinutes,
        ));
    }
}
