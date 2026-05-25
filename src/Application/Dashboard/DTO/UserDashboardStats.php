<?php

namespace App\Application\Dashboard\DTO;

readonly class UserDashboardStats
{
    public function __construct(
        public int $folderDraftWorkspaces,
    ) {}
}
