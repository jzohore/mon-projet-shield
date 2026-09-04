<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class GenerateDerAcknowledgementCertificateMessage
{
    public function __construct(public string $acknowledgementSlugId)
    {
    }
}
