<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\VerifyOrias;

use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\OriasCheckOutcome;
use App\Domain\Workspace\Event\WorkspaceOriasCheckFailedEvent;
use App\Domain\Workspace\Event\WorkspaceOriasCheckSucceededEvent;
use App\Domain\Workspace\Exception\OriasRegistryUnavailableException;
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

        // Le registre ORIAS s'interroge par SIREN (le n° d'immatriculation à
        // 8 chiffres du profil n'est pas une clé de recherche de cet endpoint).
        $siren = $this->resolveSiren($workspace);
        Assert::stringNotEmpty($siren, sprintf('Aucun numéro SIREN/SIRET exploitable pour le cabinet "%s".', $workspace->name));

        $now = new \DateTimeImmutable();

        // 1. Exécution de la vérification sur le registre ORIAS
        $result = $this->oriasChecker->checkNumber($siren);

        // 1a. Registre injoignable : on ne conclut pas, on ne touche pas au profil.
        if (OriasCheckOutcome::UNAVAILABLE === $result->outcome) {
            $this->logger->warning('Vérification ORIAS impossible : registre indisponible.', [
                'workspace' => $slugId,
                'siren' => $siren,
                'reason' => $result->errorMessage,
            ]);

            throw new OriasRegistryUnavailableException($result->errorMessage ?? 'Le registre ORIAS est temporairement injoignable.');
        }

        if (!$result->isValid()) {
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

        // 1b. Le SIREN est bien inscrit, mais sous un autre n° ORIAS que celui déclaré : on trace.
        $declaredOrias = null !== $profile->oriasNumber ? preg_replace('/\D+/', '', $profile->oriasNumber) : null;
        if (null !== $declaredOrias && '' !== $declaredOrias
            && null !== $result->registeredOriasNumber
            && $declaredOrias !== $result->registeredOriasNumber) {
            $this->logger->warning('Incohérence n° ORIAS déclaré / registre.', [
                'workspace' => $slugId,
                'siren' => $siren,
                'declared' => $declaredOrias,
                'registered' => $result->registeredOriasNumber,
            ]);
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

    /**
     * SIREN du cabinet, dérivé du SIRET (9 premiers chiffres) si le champ SIREN
     * est absent ou mal renseigné. `null` si rien d'exploitable.
     */
    private function resolveSiren(Workspace $workspace): ?string
    {
        foreach ([$workspace->siren, $workspace->siret] as $candidate) {
            $digits = null !== $candidate ? (string) preg_replace('/\D+/', '', $candidate) : '';

            if (9 === \strlen($digits)) {
                return $digits;
            }

            if (14 === \strlen($digits)) {
                return substr($digits, 0, 9);
            }
        }

        return null;
    }
}
