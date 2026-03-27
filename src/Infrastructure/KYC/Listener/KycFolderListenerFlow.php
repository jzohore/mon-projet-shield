<?php

namespace App\Infrastructure\KYC\Listener;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\Kyc\Event\BindCompanyEvent;
use App\Domain\Kyc\Event\CompanyResetEvent;
use App\Domain\Kyc\Event\CreateStakeholderEvent;
use App\Domain\Kyc\Event\KycFolderCreatedEvent;
use App\Domain\Kyc\Event\KycFolderSubmittedEvent;
use App\Domain\Kyc\Event\RemoveStakeholderEvent;
use App\Domain\Kyc\Event\UpdatePercentageStakeholderEvent;
use App\Domain\Kyc\Event\UploadKycDocumentEvent;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Infrastructure\KYC\Message\SendCreatedKycFolderMessage;
use App\Infrastructure\KYC\Message\SendSubmittedKycFolderMessage;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class KycFolderListenerFlow
{
    public function __construct(
        private CreateAuditLogUseCase $auditLogUseCase,
        private MessageBusInterface $messageBus,
        private Security $security,
        private KycFolderRepositoryInterface $kycFolderRepository,
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

    #[AsEventListener]
    public function logCreatedHistory(KycFolderCreatedEvent $event): void
    {
        $folder = $event->kycFolder;
        $user = $this->security->getUser();
        $email = $user?->getUserIdentifier() ?? 'utilisateur inconnu';
        $folder->saveHistory(
            'Demande initiée',
            sprintf('Dossier créé par : %s', $email)
        );
        $this->kycFolderRepository->save($folder);
    }

    #[AsEventListener]
    public function logBindCompanyHistory(BindCompanyEvent $event): void
    {
        $folder = $event->kycFolder;
        $folder->saveHistory(
            'Entreprise identifiée',
            sprintf('SIREN : %s (Via API)', $folder->siren)
        );
        $this->kycFolderRepository->save($folder);
    }

    #[AsEventListener]
    public function logSaveStakeholderHistory(CreateStakeholderEvent $event): void
    {
        $folder = $event->kycFolder;
        $count = $folder->stakeholders->count();

        $folder->saveHistory(
            'Mise à jour des intervenants',
            sprintf(
                'La liste des dirigeants et bénéficiaires a été mise à jour (%d personne%s identifiée%s).',
                $count,
                $count > 1 ? 's' : '',
                $count > 1 ? 's' : ''
            )
        );
        $this->kycFolderRepository->save($folder);
    }

    #[AsEventListener]
    public function logRemoveStakeholderHistory(RemoveStakeholderEvent $event): void
    {
        $folder = $event->kycFolder;
        $name = $event->stakeholderName ?? 'Un intervenant';

        $folder->saveHistory(
            'Retrait d\'un intervenant',
            sprintf(
                'L\'intervenant %s a été supprimé. Le dossier compte désormais %d personne(s).',
                $name,
                $folder->stakeholders->count()
            )
        );

        $this->kycFolderRepository->save($folder);
    }

    #[AsEventListener]
    public function logUpdatePercentageStakeholderHistory(UpdatePercentageStakeholderEvent $event): void
    {
        $folder = $event->kycFolder;
        $stakeholder = $event->stakeholder;

        $folder->saveHistory(
            'Modification des parts sociales',
            sprintf(
                'Le pourcentage de détention de %s %s a été mis à jour (%d%%).',
                $stakeholder->firstName,
                $stakeholder->lastName,
                $stakeholder->ownershipPercentage
            )
        );

        $this->kycFolderRepository->save($folder);
    }

    #[AsEventListener]
    public function logUploadDocumentHistory(UploadKycDocumentEvent $event): void
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

    #[AsEventListener]
    public function logSubmittedKycHistory(KycFolderSubmittedEvent $event): void
    {
        $folder = $event->kycFolder;

        $folder->saveHistory(
            'Dossier soumis',
            sprintf(
                'Certification et soumission du dossier de l\'entreprise %s (%s).',
                $folder->companyName,
                $folder->siren
            )
        );
        $this->kycFolderRepository->save($folder);
    }

    /**
     * @throws ExceptionInterface
     */
    #[AsEventListener]
    public function dispatchEmailSubmittedKycFolderCreated(KycFolderSubmittedEvent $event): void
    {
        $kyc = $event->kycFolder;
        $message = new SendSubmittedKycFolderMessage(
            $kyc->slugId,
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
        $message = new SendCreatedKycFolderMessage(
            $kyc->slugId,
        );
        $this->messageBus->dispatch($message);
    }

    #[AsEventListener]
    public function logResetHistory(CompanyResetEvent $event): void
    {
        $folder = $event->folder;
        $folder->saveHistory(
            'Réinitialisation de l\'entreprise',
            'Les informations morales et les bénéficiaires ont été retirés du dossier.'
        );
        $this->kycFolderRepository->save($folder);
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
