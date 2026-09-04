<?php

declare(strict_types=1);

namespace App\Application\Portal\UseCase;

use App\Application\Portal\DTO\DocumentItemDto;
use App\Application\Portal\DTO\FolderDetailDto;
use App\Domain\Compliance\Exception\ComplianceFolderNotFoundException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\User\Entity\Client;
use App\Domain\User\Enum\ClientPortalStatus;

readonly class GetFolderDetailUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $folderRepository,
        private ComplianceDocumentRepositoryInterface $documentRepository,
    ) {
    }

    public function __invoke(Client $client, string $folderId): FolderDetailDto
    {
        // 1. Récupération sécurisée : On s'assure que le dossier appartient bien à CE client.
        $folder = $this->folderRepository->findOneBySlugIdAndClient($folderId, $client);

        if (!$folder instanceof \App\Domain\Compliance\Entity\ComplianceFolder) {
            throw ComplianceFolderNotFoundException::withId($folderId);
        }

        // 2. Récupération des documents liés
        $documents = $this->documentRepository->findByFolder($folder);

        // 3. Mapping des documents vers le sous-DTO
        $documentDtos = array_map(static fn (\App\Domain\Compliance\Entity\ComplianceDocument $doc): DocumentItemDto => new DocumentItemDto(
            id: $doc->slugId,
            name: $doc->type->getLabel(), // Suppose un Enum DocumentType
            status: $doc->status,
            uploadedAtFormatted: $doc->uploadedAt?->format('d/m/Y à H:i'),
            rejectionReason: $doc->rejectionReason,
        ), $documents);

        // 4. Assemblage final
        return new FolderDetailDto(
            id: $folder->slugId,
            title: $folder->title ?? 'Dossier de conformité',
            reference: $folder->reference,
            openedAtFormatted: $folder->createdAt->format('d/m/Y'),
            status: ClientPortalStatus::fromFolderStatus($folder->status),
            documents: $documentDtos,
        );
    }
}
