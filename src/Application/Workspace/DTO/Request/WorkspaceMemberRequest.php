<?php

namespace App\Application\Workspace\DTO\Request;

use App\Domain\Workspace\Enum\InvitedRole;

class WorkspaceMemberRequest
{
    public ?string $userSlugId = null;

    public ?string $workspaceSlugId = null;

    public ?InvitedRole $role = null;
}
