<?php

declare(strict_types=1);

namespace App\Application\Workspace\DTO\Request;

class WorkspaceInvitationRequest
{
    public ?string $email = null;

    public ?string $slugId = null;
}
