<?php

declare(strict_types=1);

namespace App\Application\Portal\DTO;

use App\Application\Compliance\DTO\Request\DocuSealInfo;
use App\Domain\Kyc\Enum\DocumentStatus;

readonly class DocumentItemDto
{
    public function __construct(
        public string $id,
        public string $name, // Ex: "Carte d'identité"
        public DocumentStatus $status, // Ex: PENDING, VALIDATED, REJECTED
        public ?string $uploadedAtFormatted = null,
        public ?string $rejectionReason = null,
        public ?DocuSealInfo $docuSealInfo = null,
    ) {
    }
}
