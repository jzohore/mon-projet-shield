<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\Client;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Event\ClientDetachedFromWorkspaceEvent;
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
 * Retire un client du portefeuille du cabinet : détache le rattachement et
 * supprime ses dossiers brouillons vierges. Autorisé uniquement si AUCUN dossier
 * du client (dans ce cabinet) ne porte de preuve ni de verrou de litige — sinon
 * c'est une clôture de relation, pas un retrait. Ne supprime jamais le compte
 * global, n'envoie aucun e-mail.
 */
readonly class RemoveClientFromWorkspaceUseCase
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

    public function __invoke(string $clientSlugId): void
    {
        $workspace = $this->workspaceProvider->getWorkspace();
        $actor = $this->userProvider->getUser();

        if (!$this->workspaceMemberRepository->isUserAdminOfWorkspace($actor, $workspace)) {
            throw new \DomainException('Retirer un client du portefeuille est réservé aux administrateurs du cabinet.');
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
            if ($folder->isUnderLegalHold) {
                throw new \DomainException('Un verrou de litige est posé : ce client ne peut pas être retiré, clôturez la relation.');
            }
            if ($folder->carriesEvidence() || null !== $folder->relationshipEndedAt) {
                throw new \DomainException('Ce client a au moins un dossier engagé : utilisez « Clôturer la relation d\'affaires ».');
            }
        }

        $deletedSlugIds = [];
        $this->transactionManager->transactional(function () use ($folders, $client, $workspace, $actor, &$deletedSlugIds): void {
            foreach ($folders as $folder) {
                $folder->markAsDeleted('Retrait du client du portefeuille (dossier vierge).', $actor->getFullName());
                $this->folderRepository->save($folder, flush: false);
                $deletedSlugIds[] = $folder->slugId;
            }

            $client->detachFromWorkspace($workspace);
            $this->clientRepository->save($client);
        });

        $this->eventDispatcher->dispatch(new ClientDetachedFromWorkspaceEvent(
            clientSlugId: $client->slugId,
            workspaceSlugId: $workspace->slugId,
            actorName: $actor->getFullName(),
            actorSlugId: $actor->slugId,
            deletedDraftSlugIds: $deletedSlugIds,
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
