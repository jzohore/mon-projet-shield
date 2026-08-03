<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\VerifySiret;

use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceSiretCheckFailedEvent;
use App\Domain\Workspace\Event\WorkspaceSiretVerifiedEvent;
use App\Domain\Workspace\Exception\WorkspaceNotFoundException;
use App\Domain\Workspace\Gateway\SiretCheckerInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

final readonly class VerifyWorkspaceSiretUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private SiretCheckerInterface $siretChecker,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(string $slugId, string $performedByEmail): void
    {
        $workspace = $this->workspaceRepository->findOneBySlug($slugId);
        if (!$workspace instanceof Workspace) {
            throw WorkspaceNotFoundException::withSlug($slugId);
        }

        $siretValue = $workspace->siret;
        Assert::stringNotEmpty(
            $siretValue,
            sprintf('Aucun numéro SIRET/SIREN renseigné pour le cabinet "%s".', $workspace->name)
        );

        $now = new \DateTimeImmutable();
        // Appel au Port d'infrastructure (API Gouv / Pappers)
        $sirenResult = $this->siretChecker->verifyStatus($siretValue, $workspace->name);

        if (!$sirenResult->isActive) {
            // Traitement du cas d'échec / Fermeture administrative
            $workspace->markSiretAsInvalid(
                etatAdministratif: $sirenResult->etatAdministratif,
                reason: $sirenResult->message
            );

            $this->workspaceRepository->save($workspace);

            $this->eventDispatcher->dispatch(new WorkspaceSiretCheckFailedEvent(
                workspace: $workspace,
                siretNumber: $siretValue,
                errorMessage: $sirenResult->message,
                performedByEmail: $performedByEmail,
                occurredAt: $now
            ));

            $this->logger->warning('SIRET invalide ou entreprise fermée. Cabinet suspendu.', [
                'workspace_slug' => $slugId,
                'etat_administratif' => $sirenResult->etatAdministratif,
                'performed_by' => $performedByEmail,
            ]);

            return; // ⛔ IMPORTANT : On stoppe l'exécution ici pour éviter le double save
        }

        // Traitement du cas nominal (Succès)
        $workspace->updateSiretStatus(
            isSiretValid: true,
            etatAdministratif: $sirenResult->etatAdministratif
        );

        $this->workspaceRepository->save($workspace);

        $this->eventDispatcher->dispatch(new WorkspaceSiretVerifiedEvent(
            workspace: $workspace,
            messageResult: $sirenResult->message,
            performedByEmail: $performedByEmail,
            occurredAt: $now,
        ));

        $this->logger->info('Numéro SIRET vérifié avec succès.', [
            'workspace_slug' => $slugId,
            'performed_by' => $performedByEmail,
        ]);
    }
}
