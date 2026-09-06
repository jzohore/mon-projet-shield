<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener;

use App\Domain\Compliance\Event\ClientRelationshipEndedEvent;
use App\Domain\User\Entity\Client;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Infrastructure\Compliance\Message\SendClientRelationshipEndedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Un seul e-mail au client par clôture de relation (l'événement chapeau n'est
 * émis qu'une fois par le use case). Contenu factuel, **sans le motif**
 * (art. L.561-18 CMF). Expéditeur logique : le cabinet.
 */
#[AsEventListener]
readonly class SendClientRelationshipEndedEmailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ClientRepositoryInterface $clientRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ClientRelationshipEndedEvent $event): void
    {
        $workspace = $this->workspaceRepository->findOneBySlug($event->workspaceSlugId);
        if (!$workspace instanceof Workspace) {
            $this->logger->warning('Clôture de relation : cabinet introuvable pour l\'e-mail.', [
                'workspace_slug_id' => $event->workspaceSlugId,
            ]);

            return;
        }

        $client = $this->clientRepository->findOneBySlugIdAndWorkspace($event->clientSlugId, $workspace);
        if (!$client instanceof Client) {
            $this->logger->warning('Clôture de relation : client introuvable pour l\'e-mail.', [
                'client_slug_id' => $event->clientSlugId,
            ]);

            return;
        }

        $this->messageBus->dispatch(new SendClientRelationshipEndedMessage(
            clientEmail: $client->email,
            clientName: $client->getFullName(),
            workspaceName: $workspace->name,
            workspaceContactEmail: $workspace->email,
            purgeDueAtFormatted: $event->latestPurgeDueAt?->format('d/m/Y'),
        ));
    }
}
