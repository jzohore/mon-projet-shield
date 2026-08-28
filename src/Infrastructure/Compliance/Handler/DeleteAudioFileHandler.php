<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Handler;

use App\Domain\Port\DocumentStorageInterface;
use App\Infrastructure\Compliance\Message\DeleteAudioFileMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeleteAudioFileHandler
{
    public function __construct(
        private DocumentStorageInterface $storage,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function __invoke(DeleteAudioFileMessage $message): void
    {
        try {
            $this->storage->delete($message->filePath);
        } catch (\Throwable $e) {
            $this->logger->error('Impossible de supprimer le fichier ' . $message->filePath, [
                'filePath' => $message->filePath,
                'exception' => $e,
            ]);
            throw $e; // pour déclencher le retry_strategy configuré dans messenger.yaml
        }
    }
}
