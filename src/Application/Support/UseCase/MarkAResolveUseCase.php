<?php

declare(strict_types=1);

namespace App\Application\Support\UseCase;

use App\Domain\Support\Entity\SupportThread;
use App\Domain\Support\Repository\SupportThreadRepositoryInterface;

final readonly class MarkAResolveUseCase
{
    public function __construct(
        private SupportThreadRepositoryInterface $threadRepository,
    ) {
    }

    public function execute(SupportThread $thread): void
    {
        // Si c't l'Admin qui lit, on marque les messages du CLIENT comme lus.
        $thread->resolve();
        $this->threadRepository->save($thread);
    }
}
