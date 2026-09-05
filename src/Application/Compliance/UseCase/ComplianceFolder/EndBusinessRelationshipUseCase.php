<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Event\BusinessRelationshipEndedEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Le cabinet acte la fin de la relation d'affaires pour un dossier : le délai de
 * conservation LCB-FT de 5 ans démarre (art. L.561-12 CMF). Acte à effet légal :
 * réservé à un administrateur du cabinet, motif obligatoire, tracé.
 */
readonly class EndBusinessRelationshipUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $folderRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private CurrentUserProvider $currentUserProvider,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(ComplianceFolder $folder, string $reason): void
    {
        $user = $this->currentUserProvider->getUser();

        if (!$this->workspaceMemberRepository->isUserAdminOfWorkspace($user, $folder->workspace) || !$folder->canBeViewedBy($user)) {
            throw new \DomainException('Clôturer une relation d\'affaires est réservé aux administrateurs du cabinet ayant accès à ce dossier.');
        }

        // Gardes (déjà clôturé, motif vide, brouillon) portées par l'entité.
        $folder->endBusinessRelationship($reason, $user->getFullName());

        $this->transactionManager->transactional(fn () => $this->folderRepository->save($folder));

        $purgeDueAt = $folder->purgeDueAt;
        if (!$purgeDueAt instanceof \DateTimeImmutable) {
            throw new \DomainException('L\'échéance de purge n\'a pas été calculée.');
        }

        $this->eventDispatcher->dispatch(new BusinessRelationshipEndedEvent(
            folderSlugId: $folder->slugId,
            reason: trim($reason),
            actorName: $user->getFullName(),
            actorSlugId: $user->slugId,
            purgeDueAt: $purgeDueAt,
        ));
    }
}
