<?php

declare(strict_types=1);

namespace App\Infrastructure\Ocr\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Ocr\Event\OcrEvent;
use Exception;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogOcrListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(OcrEvent $event): void
    {
        $document = $event->kycDocument;
        $user = $event->user;

        Assert::notNull($user->id);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::KYC_DOCUMENT_OCR,
            payload: [
                'document' => $document->slugId,
                'type' => $document->type,
            ],
            actor: $user->id->toString(),
            workspace: $user->workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
