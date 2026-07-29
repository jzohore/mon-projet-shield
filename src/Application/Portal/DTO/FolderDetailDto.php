<?php

declare(strict_types=1);

namespace App\Application\Portal\DTO;

use App\Domain\User\Enum\ClientPortalStatus;

readonly class FolderDetailDto
{
    public function __construct(
        public string $id,
        public string $title,
        public string $reference,
        public string $openedAtFormatted,
        public ClientPortalStatus $status,
        /** @var array<DocumentItemDto> */
        public array $documents,
    ) {
    }
}
