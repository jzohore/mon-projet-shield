<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener;

use App\Application\Compliance\DTO\Request\LogMeetingRecordingRequest;
use App\Application\Compliance\UseCase\ComplianceFolder\LogMeetingRecordingUseCase;
use App\Domain\Compliance\Event\MeetingAudioFinalizedEvent;
use App\Infrastructure\Compliance\Message\AnalyzeCompleteMeetingMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class CreateMeetingRecordingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LogMeetingRecordingUseCase $logMeetingRecordingUseCase,
        private MessageBusInterface $messageBus,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // On branche l'écouteur sur l'événement métier
        return [
            MeetingAudioFinalizedEvent::class => 'onAudioFinalized',
        ];
    }

    /**
     * 🛡️ AUDIT TRAIL : Crée la trace immuable en base de données.
     */
    public function onAudioFinalized(MeetingAudioFinalizedEvent $event): void
    {
        // 1. On prépare la commande
        $request = new LogMeetingRecordingRequest(
            folderSlugId: $event->folderSlugId,
            sessionId: $event->sessionId,
            s3Path: $event->s3Path,
            consumedSeconds: $event->consumedSeconds,
        );

        // 🚀 2. On exécute le Use Case ET on récupère l'ID
        $recordingId = ($this->logMeetingRecordingUseCase)($request);

        // 3. On délègue l'analyse IA au Worker asynchrone, en lui passant la clé exacte
        $this->messageBus->dispatch(new AnalyzeCompleteMeetingMessage(
            recordingId: $recordingId
        ));
    }
}
