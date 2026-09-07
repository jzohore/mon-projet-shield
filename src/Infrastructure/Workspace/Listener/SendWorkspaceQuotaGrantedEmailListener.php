<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener;

use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceQuotaGrantedEvent;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Infrastructure\Workspace\Message\SendWorkspaceQuotaGrantedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener]
readonly class SendWorkspaceQuotaGrantedEmailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(WorkspaceQuotaGrantedEvent $event): void
    {
        $workspace = $this->workspaceRepository->findOneBySlug($event->workspaceSlugId);
        if (!$workspace instanceof Workspace || '' === trim($workspace->email)) {
            $this->logger->warning('Recharge de quota : e-mail du cabinet introuvable, notification ignorée.', [
                'workspace_slug_id' => $event->workspaceSlugId,
            ]);

            return;
        }

        $this->messageBus->dispatch(new SendWorkspaceQuotaGrantedMessage(
            recipientEmail: $workspace->email,
            workspaceName: $workspace->name,
            dossiersGranted: $event->dossiersGranted,
            minutesGranted: $event->minutesGranted,
            trialDossiersRemaining: $event->trialDossiersRemaining,
            remainingMinutes: $event->remainingMinutes,
        ));
    }
}
