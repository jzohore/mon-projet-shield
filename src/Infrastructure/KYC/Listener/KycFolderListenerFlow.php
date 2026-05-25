<?php

namespace App\Infrastructure\KYC\Listener;

use App\Domain\Kyc\Event\CompanyResetEvent;
use App\Domain\Kyc\Event\KycFolderCreatedEvent;
use App\Domain\Kyc\Event\KycFolderSubmittedEvent;
use App\Infrastructure\KYC\Message\SendCreatedKycFolderMessage;
use App\Infrastructure\KYC\Message\SendSubmittedKycFolderMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class KycFolderListenerFlow
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $router,
    ) {}

    //    #[AsEventListener]
    //    public function auditLog(KycFolderCreatedEvent $event): void
    //    {
    //        $kyc = $event->kycFolder;
    //        $auditLog = new CreateAuditLogRequest(
    //            eventName: AuditEventType::KYC_FOLDER_INITIATED,
    //            resourceId: $kyc->slugId,
    //            data: [
    //                'contact_email' => $kyc->contactEmail,
    //                'first_name' => $kyc->contactFirstName,
    //                'last_name' => $kyc->contactLastName,
    //                'workspace_by' => $kyc->workspace->name,
    //            ]
    //        );
    //
    //        ($this->auditLogUseCase)($auditLog);
    //    }

    /**
     * @throws ExceptionInterface
     */
    #[AsEventListener]
    public function dispatchEmailSubmittedKycFolderCreated(KycFolderSubmittedEvent $event): void
    {
        $kyc = $event->kycFolder;
        $url = $this->router->generate('app_kyc_show', [
            'slugId' => $kyc->slugId,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $message = new SendSubmittedKycFolderMessage(
            $kyc->slugId,
            $url,
        );
        $this->messageBus->dispatch($message);
    }

    /**
     * @throws ExceptionInterface
     */
    #[AsEventListener]
    public function dispatchEmailKycFolderCreated(KycFolderCreatedEvent $event): void
    {
        $kyc = $event->kycFolder;

        $url = $this->router->generate('portal_kyc_confirm_token', [
            'token' => $kyc->shareToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $message = new SendCreatedKycFolderMessage(
            $kyc->slugId,
            $url,
        );
        $this->messageBus->dispatch($message);
    }


    #[AsEventListener]
    public function cleanupStorage(CompanyResetEvent $event): void
    {
        //        foreach ($event->oldDocuments as $doc) {
        //            // Ici tu appelleras ton futur service Scaleway
        //            // $this->storage->delete($doc->getPath());
        //        }
    }
}
