<?php

declare(strict_types=1);

namespace App\Infrastructure\Ocr\Listener;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\Ocr\Event\OcrEvent;
use App\Infrastructure\Ocr\Message\ProcessOcrMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class OcrFlowListener
{
    public function __construct(
        private CreateAuditLogUseCase $auditLogUseCase,
        private MessageBusInterface $messageBus,
    ) {}

    #[AsEventListener]
    public function auditLog(OcrEvent $event): void
    {
        $document = $event->kycDocument;
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::KYC_DOCUMENT_OCR,
            resourceId: $document->folder->workspace->slugId,
            data: [
                'document' => $document->slugId,
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }

    #[AsEventListener]
    public function dispatchToOcr(OcrEvent $event): void
    {
        // On envoie le message dans le bus (Messenger)
        $this->messageBus->dispatch(
            new ProcessOcrMessage($event->kycDocument->slugId)
        );
    }
}
