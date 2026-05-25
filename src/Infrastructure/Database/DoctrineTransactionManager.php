<?php

namespace App\Infrastructure\Database;

use App\Domain\Database\TransactionManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function transactional(callable $operation): mixed
    {
        // La magie de Doctrine : wrapInTransaction gère le begin, le commit et le rollback !
        return $this->entityManager->wrapInTransaction($operation);
    }
}
