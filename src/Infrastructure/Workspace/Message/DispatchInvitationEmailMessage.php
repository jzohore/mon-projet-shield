<?php

namespace App\Infrastructure\Workspace\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
final readonly class DispatchInvitationEmailMessage
{
    public function __construct(
        public ?string $workspaceSlugId,
    ) {}
}
