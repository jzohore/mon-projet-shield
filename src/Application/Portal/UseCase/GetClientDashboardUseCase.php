<?php

declare(strict_types=1);

namespace App\Application\Portal\UseCase;

use App\Application\Portal\DTO\ActiveFolderDto;
use App\Application\Portal\DTO\ClientDashboardDto;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\User\Entity\Client;
use App\Domain\User\Enum\ClientPortalStatus;
use Psr\Log\LoggerInterface;

readonly class GetClientDashboardUseCase
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $documentRepository,
        private ComplianceFolderRepositoryInterface $folderRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(Client $client): ClientDashboardDto
    {
        // 1. Récupération du dossier actif (La source de vérité)
        $activeFolder = $this->folderRepository->findActiveForClient($client);

        // 2. 🚨 INVARIANT MÉTIER KYSURE
        // Si le client se connecte, son DER est signé. Il DOIT avoir un dossier actif.
        if (!$activeFolder instanceof \App\Domain\Compliance\Entity\ComplianceFolder) {
            $this->logger->critical('Anomalie DDD : Un client sans dossier actif a réussi à se connecter au portail.', [
                'client_id' => $client->slugId,
            ]);

            throw new \LogicException(sprintf('Incohérence de domaine : Aucun dossier actif trouvé pour le client %s.', $client->slugId));
        }

        // 3. Traduction de l'état du domaine (CGP) vers l'état UI (Client)
        $portalStatus = ClientPortalStatus::fromFolderStatus($activeFolder->status);

        // 4. Optimisation I/O : On ne compte les documents que si une action est requise
        $pendingDocs = 0;
        if (ClientPortalStatus::ACTION_REQUIRED === $portalStatus) {
            $pendingDocs = $this->documentRepository->countPendingForClient($client);
        }

        // 5. Gestion sécurisée de la collection Workspaces (Multi-CGP)
        $firstWorkspace = $client->workspaces->first();
        $cabinetName = (false !== $firstWorkspace) ? $firstWorkspace->name : 'Votre Cabinet';

        // 6. Création du Sous-DTO représentant le dossier
        $activeFolderDto = new ActiveFolderDto(
            id: $activeFolder->slugId ?? (string) $activeFolder->id,
            title: $activeFolder->title ?? 'Dossier de Conformité (KYC)',
            openedAtFormatted: $activeFolder->createdAt->format('d/m/Y'),
            status: $portalStatus,
            workspaceName: $cabinetName,
        );

        // 7. Assemblage et retour du ViewModel global
        return new ClientDashboardDto(
            clientFirstName: $client->firstName,
            cabinetName: $cabinetName,
            portalStatus: $portalStatus,
            pendingDocumentsCount: $pendingDocs,
            activeFolder: $activeFolderDto, // 🚨 Injection de l'agrégat
        );
    }
}
