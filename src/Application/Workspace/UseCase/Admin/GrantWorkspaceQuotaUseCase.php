<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Admin;

use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceQuotaGrantedEvent;
use App\Domain\Workspace\Exception\WorkspaceNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Recharge administrateur : ajoute des dossiers d'essai et/ou des minutes
 * d'entretien à un workspace. Notifie le cabinet par e-mail + journal d'audit.
 * Idempotence : non pertinente ici (acte manuel, montant explicite) — mais un
 * appel à 0 / 0 est rejeté.
 */
readonly class GrantWorkspaceQuotaUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(
        string $workspaceSlugId,
        int $dossiers,
        int $minutes,
        string $reason,
        string $actorName,
        string $actorSlugId,
    ): void {
        $dossiers = max(0, $dossiers);
        $minutes = max(0, $minutes);

        if (0 === $dossiers && 0 === $minutes) {
            throw new \DomainException('Indiquez au moins un dossier ou une minute à créditer.');
        }

        $reason = trim($reason);
        if ('' === $reason) {
            throw new \DomainException('Un motif est obligatoire pour une recharge.');
        }

        $workspace = $this->workspaceRepository->findOneBySlug($workspaceSlugId);
        if (!$workspace instanceof Workspace) {
            throw WorkspaceNotFoundException::withSlug($workspaceSlugId);
        }

        $this->transactionManager->transactional(function () use ($workspace, $dossiers, $minutes): void {
            if ($dossiers > 0) {
                $workspace->grantTrialComplianceFolders($dossiers);
            }
            if ($minutes > 0) {
                $workspace->grantMeetingMinutes($minutes);
            }
            $this->workspaceRepository->save($workspace);
        });

        $this->eventDispatcher->dispatch(new WorkspaceQuotaGrantedEvent(
            workspaceSlugId: $workspace->slugId,
            dossiersGranted: $dossiers,
            minutesGranted: $minutes,
            trialDossiersRemaining: $workspace->trialDossiersRemaining,
            remainingMinutes: $workspace->remainingMeetingMinutes(),
            reason: $reason,
            actorName: $actorName,
            actorSlugId: $actorSlugId,
        ));
    }
}
