<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener\MeetingRecord;

use App\Domain\Compliance\Event\MeetingAudioDeletedEvent;
use App\Infrastructure\Compliance\Message\DeleteAudioFileMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener]
final readonly class DeleteAudioFromS3Listener
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(MeetingAudioDeletedEvent $event): void
    {
        $this->messageBus->dispatch(new DeleteAudioFileMessage(
            filePath: $event->filePath,
        ));
    }
}
