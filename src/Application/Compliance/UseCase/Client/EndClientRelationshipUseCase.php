<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\Client;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Enum\RelationshipEndReason;
use App\Domain\Compliance\Event\BusinessRelationshipEndedEvent;
use App\Domain\Compliance\Event\ClientRelationshipEndedEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\Client;
use App\Domain\User\Exception\ClientNotFoundException;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Clôture la relation d'affaires avec un client, pour ce cabinet : chaque dossier
 * engagé passe par {@see ComplianceFolder::endBusinessRelationship()} (démarre le
 * délai de conservation LCB-FT de 5 ans), les brouillons vierges sont supprimés.
 * Acte irréversible, réservé aux administrateurs, un seul e-mail au client.
 */
readonly class EndClientRelationshipUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private ComplianceFolderRepositoryInterface $folderRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private CurrentWorkspaceProvider $workspaceProvider,
        private CurrentUserProvider $userProvider,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $clientSlugId, RelationshipEndReason $reason): void
    {
        $workspace = $this->workspaceProvider->getWorkspace();
        $actor = $this->userProvider->getUser();

        if (!$this->workspaceMemberRepository->isUserAdminOfWorkspace($actor, $workspace)) {
            throw new \DomainException('Clôturer une relation d\'affaires est réservé aux administrateurs du cabinet.');
        }

        $client = $this->clientRepository->findOneBySlugIdAndWorkspace($clientSlugId, $workspace);
        if (!$client instanceof Client) {
            throw ClientNotFoundException::withEmail($clientSlugId);
        }

        $folders = $this->foldersInWorkspace($client, $workspace);
        foreach ($folders as $folder) {
            if (!$folder->canBeViewedBy($actor)) {
                throw new \DomainException('Un dossier confidentiel de ce client vous est inaccessible : opération bloquée.');
            }
        }

        $actorName = $actor->getFullName();
        /** @var list<array{slug: string, purgeDueAt: \DateTimeImmutable}> $ended */
        $ended = [];
        $deletedDraftSlugIds = [];

        $this->transactionManager->transactional(function () use ($folders, $actorName, $reason, &$ended, &$deletedDraftSlugIds): void {
            foreach ($folders as $folder) {
                // Déjà clôturé : rejeu / double-clic → on ignore.
                if (null !== $folder->relationshipEndedAt) {
                    continue;
                }

                if (!$folder->carriesEvidence()) {
                    $folder->markAsDeleted('Clôture de la relation : brouillon vierge.', $actorName);
                    $this->folderRepository->save($folder, flush: false);
                    $deletedDraftSlugIds[] = $folder->slugId;

                    continue;
                }

                $folder->endBusinessRelationship($reason->getLabel(), $actorName);
                $this->folderRepository->save($folder, flush: false);
                $ended[] = ['slug' => $folder->slugId, 'purgeDueAt' => $folder->purgeDueAt ?? new \DateTimeImmutable()];
            }
        });

        // Aucun dossier traité (rejeu intégral) → pas d'e-mail, pas de log chapeau.
        if ([] === $ended && [] === $deletedDraftSlugIds) {
            return;
        }

        // Suspension du compte : uniquement si ce cabinet est le seul lié au
        // client. Un client multi-cabinets garde son accès tant qu'une autre
        // relation est active (la suspension par cabinet sera portée plus tard
        // par une entité de jointure dédiée).
        if ($client->isActif && 1 === $client->workspaces->count()) {
            $client->deactivate();
            $this->clientRepository->save($client);
        }

        $endedFolderSlugIds = [];
        $latestPurgeDueAt = null;
        foreach ($ended as $item) {
            $this->eventDispatcher->dispatch(new BusinessRelationshipEndedEvent(
                folderSlugId: $item['slug'],
                reason: $reason->getLabel(),
                actorName: $actorName,
                actorSlugId: $actor->slugId,
                purgeDueAt: $item['purgeDueAt'],
            ));
            $endedFolderSlugIds[] = $item['slug'];
            if (!$latestPurgeDueAt instanceof \DateTimeImmutable || $item['purgeDueAt'] > $latestPurgeDueAt) {
                $latestPurgeDueAt = $item['purgeDueAt'];
            }
        }

        $this->eventDispatcher->dispatch(new ClientRelationshipEndedEvent(
            clientSlugId: $client->slugId,
            workspaceSlugId: $workspace->slugId,
            actorName: $actorName,
            actorSlugId: $actor->slugId,
            reason: $reason,
            endedFolderSlugIds: $endedFolderSlugIds,
            deletedDraftSlugIds: $deletedDraftSlugIds,
            latestPurgeDueAt: $latestPurgeDueAt,
        ));
    }

    /**
     * @return list<ComplianceFolder>
     */
    private function foldersInWorkspace(Client $client, Workspace $workspace): array
    {
        $folders = [];
        foreach ($client->complianceFolders as $folder) {
            if ($folder->workspace === $workspace && ComplianceFolderStatus::DELETED !== $folder->status) {
                $folders[] = $folder;
            }
        }

        return $folders;
    }
}
