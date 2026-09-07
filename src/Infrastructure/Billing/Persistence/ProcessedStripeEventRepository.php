<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Persistence;

use App\Domain\Billing\Entity\ProcessedStripeEvent;
use App\Domain\Billing\Repository\ProcessedStripeEventRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class ProcessedStripeEventRepository implements ProcessedStripeEventRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function isProcessed(string $eventId): bool
    {
        return null !== $this->entityManager->find(ProcessedStripeEvent::class, $eventId);
    }

    public function markProcessed(string $eventId): void
    {
        if ($this->isProcessed($eventId)) {
            return;
        }

        $this->entityManager->persist(ProcessedStripeEvent::record($eventId));
        $this->entityManager->flush();
    }
}
