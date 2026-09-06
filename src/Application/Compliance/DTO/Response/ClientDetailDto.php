<?php

declare(strict_types=1);

namespace App\Application\Compliance\DTO\Response;

readonly class ClientDetailDto
{
    /**
     * @param list<ClientFolderSummaryDto> $folders
     */
    public function __construct(
        public string $slugId,
        public string $fullName,
        public string $email,
        public ?string $phoneNumber,
        public string $createdAtFormatted,
        public ?string $clientSinceFormatted,
        public array $folders,
        /** Aucune preuve nulle part : le client peut être retiré du portefeuille. */
        public bool $canBeRemoved,
        /** Au moins un dossier porte une relation d'affaires encore ouverte : la clôture est possible. */
        public bool $canCloseRelationship,
        /** Motif exact quand une action est indisponible (affiché sur le bouton désactivé). */
        public ?string $removalBlockedReason,
    ) {
    }
}
