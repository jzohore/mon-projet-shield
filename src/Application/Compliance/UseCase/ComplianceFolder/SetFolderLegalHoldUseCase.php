<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Event\FolderLegalHoldChangedEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Pose ou lève un verrou de litige sur un dossier (contentieux, Tracfin, demande
 * d'une autorité). Tant qu'il est posé, aucune purge n'est possible, même
 * échéance de conservation dépassée. Réservé à un administrateur du cabinet.
 */
readonly class SetFolderLegalHoldUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $folderRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private CurrentUserProvider $currentUserProvider,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(ComplianceFolder $folder, bool $place, ?string $reason = null): void
    {
        $user = $this->currentUserProvider->getUser();

        if (!$this->workspaceMemberRepository->isUserAdminOfWorkspace($user, $folder->workspace) || !$folder->canBeViewedBy($user)) {
            throw new \DomainException('Poser ou lever un verrou de litige est réservé aux administrateurs du cabinet ayant accès à ce dossier.');
        }

        if ($place) {
            $folder->placeLegalHold((string) $reason, $user->getFullName());
        } else {
            $folder->liftLegalHold($user->getFullName());
        }

        $this->transactionManager->transactional(fn () => $this->folderRepository->save($folder));

        $this->eventDispatcher->dispatch(new FolderLegalHoldChangedEvent(
            folderSlugId: $folder->slugId,
            placed: $place,
            reason: $place ? trim((string) $reason) : null,
            actorName: $user->getFullName(),
            actorSlugId: $user->slugId,
        ));
    }
}
