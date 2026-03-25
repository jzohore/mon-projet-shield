<?php

namespace App\Infrastructure\KYC\Listener;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\Kyc\Event\KycFolderCreatedEvent;
use App\Infrastructure\KYC\Message\SendCreatedKycFolderMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class KycFolderListenerFlow
{
    public function __construct(
        private CreateAuditLogUseCase $auditLogUseCase,
        private MessageBusInterface $messageBus,
    ) {}

    #[AsEventListener]
    public function auditLog(KycFolderCreatedEvent $event): void
    {
        $kyc = $event->kycFolder;
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::KYC_FOLDER_INITIATED,
            resourceId: $kyc->slugId,
            data: [
                'contact_email' => $kyc->contactEmail,
                'first_name' => $kyc->contactFirstName,
                'last_name' => $kyc->contactLastName,
                'workspace_by' => $kyc->workspace->name,
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }

    /**
     * @throws ExceptionInterface
     */
    #[AsEventListener]
    public function dispatchEmailKycFolderCreated(KycFolderCreatedEvent $event): void
    {
        $kyc = $event->kycFolder;
        $message = new SendCreatedKycFolderMessage(
            $kyc->slugId,
        );
        $this->messageBus->dispatch($message);
    }
}
