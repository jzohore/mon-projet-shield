<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener;

use App\Domain\Notifer\NotifierInterface;
use App\Domain\Workspace\Event\WorkspaceSuspendedEvent;

use function Symfony\Component\Clock\now;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
readonly class WorkspaceSuspendedNotifierSlack
{
    public function __construct(
        private NotifierInterface $notifier,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(WorkspaceSuspendedEvent $event): void
    {
        $workspace = $event->workspace;

        $texteMessage = sprintf(
            "🚨 *ALERTE - CABINET SUSPENDU*\n\n"
            . "• *Workspace :* %s\n"
            . "• *SIRET :* %s\n"
            . "• *Raison :* %s\n"
            . "• *Date :* %s\n\n"
            . "_Action requise : L'accès aux fonctionnalités DER/KYC a été automatiquement verrouillé._",
            $workspace->name,
            $workspace->siren ?? 'Non renseigné',
            $workspace->suspensionReason ?? 'Inconnue',
            now()->format('d/m/Y H:i')
        );

        $this->notifier->send($texteMessage);
    }
}
