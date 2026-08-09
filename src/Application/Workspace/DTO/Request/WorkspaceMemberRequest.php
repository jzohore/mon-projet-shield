<?php

declare(strict_types=1);

namespace App\Application\Workspace\DTO\Request;

use App\Domain\Workspace\Enum\InvitedRole;

class WorkspaceMemberRequest
{
    public function __construct(
        public string $userId,
        public string $workspaceId,
        public InvitedRole $invitedRole = InvitedRole::ROLE_WORKSPACE_ADMIN,
    ) {
    }
}
