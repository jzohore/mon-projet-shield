<?php

declare(strict_types=1);

namespace App\Application\Portal\UseCase;

use App\Application\Portal\DTO\ActiveFolderDto;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\User\Entity\Client;
use App\Domain\User\Enum\ClientPortalStatus;

readonly class GetClientFoldersUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $folderRepository,
    ) {
    }

    /**
     * Récupère et transforme la liste des dossiers actifs pour le portail client.
     *
     * @return list<ActiveFolderDto>
     */
    public function __invoke(Client $client): array
    {
        $folders = $this->folderRepository->findAllActiveForClient($client);

        return array_map(static fn (ComplianceFolder $folder): ActiveFolderDto => new ActiveFolderDto(
            id: $folder->slugId,
            title: $folder->reference ?? 'Dossier de conformité',
            openedAtFormatted: $folder->createdAt->format('d/m/Y'),
            status: ClientPortalStatus::fromFolderStatus($folder->status),
            workspaceName: $folder->workspace->name,
        ), $folders);
    }
}
