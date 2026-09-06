<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\Client;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Event\ClientAccountDeletedEvent;
use App\Domain\Compliance\Event\ClientDetachedFromWorkspaceEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\Client;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\ClientRemovalReason;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Retire un client du portefeuille du cabinet. Deux issues, tranchées par l'état
 * réel du client :
 *
 *  - **Suppression du compte** si le client n'a AUCUN dossier nulle part (tous
 *    cabinets, statut supprimé inclus) et n'est rattaché qu'à ce seul cabinet :
 *    aucune obligation de conservation LCB-FT ne s'applique (art. L.561-12 CMF :
 *    le délai court à compter d'une relation d'affaires — ici inexistante).
 *  - **Détachement** sinon : on retire le lien cabinet ↔ client et on supprime
 *    ses brouillons vierges, mais le compte survit (dossiers ou relation
 *    ailleurs). Refusé si un dossier de ce cabinet porte une preuve ou un
 *    verrou de litige → c'est alors une clôture de relation, pas un retrait.
 *
 * N'envoie aucun e-mail. Idempotent : rejeu sur un client déjà parti → sortie
 * silencieuse.
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

    public function __invoke(string $clientSlugId, ClientRemovalReason $reason): void
    {
        $workspace = $this->workspaceProvider->getWorkspace();
        $actor = $this->userProvider->getUser();

        if (!$this->workspaceMemberRepository->isUserAdminOfWorkspace($actor, $workspace)) {
            throw new \DomainException('Retirer un client du portefeuille est réservé aux administrateurs du cabinet.');
        }

        $client = $this->clientRepository->findOneBySlugIdAndWorkspace($clientSlugId, $workspace);
        if (!$client instanceof Client) {
            // Déjà retiré / supprimé : rejeu → no-op idempotent, pas d'exception.
            return;
        }

        if ($this->qualifiesForAccountDeletion($client)) {
            $this->deleteAccount($client, $workspace, $actor->getFullName(), $actor->slugId, $reason);

            return;
        }

        $this->detach($client, $workspace, $actor, $reason);
    }

    /**
     * Suppression possible : zéro dossier (tous cabinets, statut supprimé inclus)
     * et un seul rattachement cabinet.
     */
    private function qualifiesForAccountDeletion(Client $client): bool
    {
        return $client->complianceFolders->isEmpty() && 1 === $client->workspaces->count();
    }

    private function deleteAccount(
        Client $client,
        Workspace $workspace,
        string $actorName,
        string $actorSlugId,
        ClientRemovalReason $reason,
    ): void {
        $clientEmail = $client->email;
        $clientCreatedAtIso = $client->createdAt->format(\DateTimeInterface::ATOM);

        $this->transactionManager->transactional(function () use ($client): void {
            // Re-vérification dans la transaction : un collègue a pu greffer un
            // dossier entre la lecture et l'écriture.
            if (!$this->qualifiesForAccountDeletion($client)) {
                throw new \DomainException('Ce client a désormais un dossier : suppression impossible, retirez-le du portefeuille.');
            }

            $this->clientRepository->remove($client);
        });

        $this->eventDispatcher->dispatch(new ClientAccountDeletedEvent(
            clientEmail: $clientEmail,
            clientCreatedAtIso: $clientCreatedAtIso,
            workspaceSlugId: $workspace->slugId,
            actorName: $actorName,
            actorSlugId: $actorSlugId,
            reason: $reason,
            evidenceCheck: [
                'folders_count' => 0,
                'der_ack_count' => 0,
                'recordings_count' => 0,
                'workspaces_count' => 1,
            ],
        ));
    }

    private function detach(Client $client, Workspace $workspace, User $actor, ClientRemovalReason $reason): void
    {
        $wasMultiWorkspace = $client->workspaces->count() > 1;
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
        $actorName = $actor->getFullName();
        $this->transactionManager->transactional(function () use ($folders, $client, $workspace, $actorName, $reason, &$deletedSlugIds): void {
            foreach ($folders as $folder) {
                $folder->markAsDeleted(
                    sprintf('Retrait du client du portefeuille (%s).', $reason->getLabel()),
                    $actorName,
                );
                $this->folderRepository->save($folder, flush: false);
                $deletedSlugIds[] = $folder->slugId;
            }

            $client->detachFromWorkspace($workspace);
            $this->clientRepository->save($client);
        });

        $this->eventDispatcher->dispatch(new ClientDetachedFromWorkspaceEvent(
            clientSlugId: $client->slugId,
            workspaceSlugId: $workspace->slugId,
            actorName: $actorName,
            actorSlugId: $actor->slugId,
            deletedDraftSlugIds: $deletedSlugIds,
            reason: $reason,
            wasMultiWorkspace: $wasMultiWorkspace,
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
