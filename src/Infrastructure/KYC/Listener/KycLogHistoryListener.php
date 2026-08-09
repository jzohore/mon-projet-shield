<?php

declare(strict_types=1);

namespace App\Infrastructure\KYC\Listener;

use App\Domain\Kyc\Event\BindCompanyEvent;
use App\Domain\Kyc\Event\CompanyResetEvent;
use App\Domain\Kyc\Event\CreateStakeholderEvent;
use App\Domain\Kyc\Event\KycFolderCreatedEvent;
use App\Domain\Kyc\Event\KycFolderSubmittedEvent;
use App\Domain\Kyc\Event\RemoveStakeholderEvent;
use App\Domain\Kyc\Event\UpdatePercentageStakeholderEvent;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class KycLogHistoryListener
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
        private Security $security,
    ) {
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
        $name = $event->stakeholderName;

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
}
