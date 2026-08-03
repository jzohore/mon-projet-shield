<?php

declare(strict_types=1);

namespace App\Application\Support\UseCase;

use App\Domain\Support\Entity\SupportThread;
use App\Domain\Support\Enum\SupportSenderType;
use App\Domain\Support\Repository\SupportThreadRepositoryInterface;

final readonly class MarkMessagesAsReadUseCase
{
    public function __construct(
        private SupportThreadRepositoryInterface $threadRepository,
    ) {
    }

    /**
     * @param SupportSenderType $readerType Le type de personne qui est en train de lire
     */
    public function execute(SupportThread $thread, SupportSenderType $readerType): void
    {
        // Si c't l'Admin qui lit, on marque les messages du CLIENT comme lus.
        $targetSenderType = SupportSenderType::ADMIN === $readerType
            ? SupportSenderType::CLIENT
            : SupportSenderType::ADMIN;

        $hasChanges = false;

        foreach ($thread->messages as $message) {
            if ($message->senderType === $targetSenderType && null === $message->readAt) {
                $message->markAsRead();
                $hasChanges = true;
            }
        }

        // On flush seulement s'il y a eu des modifications pour économiser la BDD
        if ($hasChanges) {
            $this->threadRepository->save($thread);
        }
    }
}
