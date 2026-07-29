<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Notifier;

use App\Domain\Shared\Port\RealTimeNotifierInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final readonly class MercureRealTimeNotifier implements RealTimeNotifierInterface
{
    public function __construct(
        private HubInterface $hub,
    ) {
    }

    public function notify(string $topic, array $payload): void
    {
        $update = new Update(
            $topic,
            json_encode($payload, \JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }
}
