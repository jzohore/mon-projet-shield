<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceOriasCheckFailedEvent;
use App\Domain\Workspace\Event\WorkspaceOriasCheckSucceededEvent;
use App\Domain\Workspace\Exception\WorkspaceNotFoundException;
use App\Domain\Workspace\Gateway\OriasCheckerInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

final readonly class VerifyWorkspaceOriasUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private OriasCheckerInterface $oriasChecker,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        private RegulatoryProfileRepositoryInterface $regulatoryProfileRepository,
    ) {
    }

    public function __invoke(string $slugId, string $performedByEmail): void
    {
        $workspace = $this->workspaceRepository->findOneBySlug($slugId);
        if (!$workspace instanceof Workspace) {
            throw WorkspaceNotFoundException::withSlug($slugId);
        }

        $profile = $workspace->regulatoryProfile;
        Assert::notNull($profile, sprintf('Le cabinet "%s" ne possède pas de profil réglementaire.', $workspace->name));

        $siren = $workspace->siren;
        Assert::stringNotEmpty($siren, sprintf('Aucun numéro SIREN renseigné pour le cabinet "%s".', $workspace->name));

        $now = new \DateTimeImmutable();

        // 1. Exécution de la vérification sur le registre ORIAS
        $result = $this->oriasChecker->checkNumber($siren);

        if (!$result->isValid) {
            $profile->updateOriasStatus(isValidOrias: false, checkedAt: $now);
            $this->regulatoryProfileRepository->save($workspace->regulatoryProfile);
            // 📢 Dispatch de l'événement d'échec (Écouté par le Subscriber d'Audit Log)
            $errorMessage = $result->errorMessage;
            Assert::notNull($errorMessage);
            $this->eventDispatcher->dispatch(new WorkspaceOriasCheckFailedEvent(
                workspace: $workspace,
                oriasNumber: $siren,
                errorMessage: $errorMessage,
                performedByEmail: $performedByEmail,
                occurredAt: $now
            ));

            throw new \RuntimeException(sprintf('Échec de la vérification ORIAS : %s', $errorMessage));
        }

        // 2. Succès : Mise à jour du profil réglementaire

        $profile->updateOriasStatus(
            isValidOrias: true,
            checkedAt: $now,
        );
        $this->regulatoryProfileRepository->save($workspace->regulatoryProfile);

        // 📢 Dispatch de l'événement de succès
        $this->eventDispatcher->dispatch(new WorkspaceOriasCheckSucceededEvent(
            workspace: $workspace,
            oriasResult: $result,
            performedByEmail: $performedByEmail,
            occurredAt: $now
        ));

        $this->logger->info('Agréments ORIAS mis à jour et événement dispatché.', ['workspace' => $slugId]);
    }
}
