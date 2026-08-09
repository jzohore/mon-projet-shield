<?php

declare(strict_types=1);

namespace App\Domain\Shared\Port;

interface RealTimeNotifierInterface
{
    /**
     * @param string               $topic   La ressource concernée (ex: 'support_thread_123')
     * @param array<string, mixed> $payload Les données à envoyer
     */
    public function notify(string $topic, array $payload): void;
}
