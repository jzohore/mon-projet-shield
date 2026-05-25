<?php

namespace App\Infrastructure\KYC\Listener;

use App\Domain\Kyc\Event\KycDocumentReceivedLocalEvent;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Infrastructure\KYC\Message\ProcessAndStoreKycDocumentMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class KycDocumentReceivedLocalListener
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
        private MessageBusInterface $messageBus,
    ) {}

    //    #[AsEventListener]
    //    public function auditLog(KycDocumentReceivedLocalEvent $event): void
    //    {
    //        $kyc = $event->kycFolder;
    //        $document = $event->kycDocument;
    //        $auditLog = new CreateAuditLogRequest(
    //            eventName: AuditEventType::KYC_DOCUMENT_UPLOADED,
    //            resourceId: $kyc->workspace->slugId,
    //            data: [
    //                'uploaded_by' => $kyc->contactEmail,
    //                'first_name' => $kyc->contactFirstName,
    //                'last_name' => $kyc->contactLastName,
    //                'doc_uploaded' => $document->type->getLabel(),
    //            ]
    //        );
    //
    //        ($this->auditLogUseCase)($auditLog);
    //    }

    #[AsEventListener]
    public function logUploadDocumentHistory(KycDocumentReceivedLocalEvent $event): void
    {
        $folder = $event->kycFolder;
        $document = $event->kycDocument;
        $typeLabel = $document->type->getLabel();

        // 1. On détermine le sujet (Société ou Personne)
        $subject = "la société";
        if ($document->stakeholder !== null) {
            $subject = sprintf("%s %s", $document->stakeholder->firstName, $document->stakeholder->lastName);
        }

        // 2. On enregistre un message cohérent
        $folder->saveHistory(
            'Document téléversé',
            sprintf(
                'Le document "%s" concernant %s a été ajouté au dossier.',
                $typeLabel,
                $subject
            )
        );

        $this->kycFolderRepository->save($folder);
    }

    /**
     * @throws ExceptionInterface
     */
    #[AsEventListener]
    public function onDocumentReceived(KycDocumentReceivedLocalEvent $event): void
    {
        // Envoi du DTO vers RabbitMQ/Redis pour traitement en tâche de fond
        $this->messageBus->dispatch(new ProcessAndStoreKycDocumentMessage(
            documentId: $event->kycDocument->slugId,
            folderSlugId: $event->kycFolder->slugId,
            localTempPath: $event->localTempPath,
            mimeType: $event->mimeType,
            originalName: $event->originalName,
            oldStoragePath: $event->oldStoragePath
        ));
    }
}
