<?php

declare(strict_types=1);

namespace App\Application\Dashboard\DTO;

readonly class UserDashboardStats
{
    public function __construct(
        public int $folderDraftWorkspaces,
    ) {
    }
}
