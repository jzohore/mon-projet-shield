<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class SendClientRelationshipEndedMessage
{
    public function __construct(
        public string $clientEmail,
        public string $clientName,
        public string $workspaceName,
        public ?string $workspaceContactEmail,
        public ?string $purgeDueAtFormatted,
    ) {
    }
}
