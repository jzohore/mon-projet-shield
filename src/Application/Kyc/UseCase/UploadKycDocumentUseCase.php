<?php

namespace App\Application\Kyc\UseCase;

use App\Application\Kyc\DTO\Request\UploadKycDocumentRequest;
use App\Application\Kyc\Port\DocumentStorageInterface;
use App\Domain\Kyc\Entity\KycDocument;
use App\Domain\Kyc\Enum\DocumentType;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Domain\Kyc\Repository\StakeholderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class UploadKycDocumentUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentStorageInterface $storage,
        private ValidatorInterface $validator,
        private KycFolderRepositoryInterface $kycFolderRepository,
        private StakeholderRepositoryInterface $stakeholderRepository,
    ) {}

    public function __invoke(UploadKycDocumentRequest $request): void
    {
        $this->validator->validate($request);

        // 1. On récupère le dossier parent
        $folder = $this->kycFolderRepository->findBySlugId($request->folderSlugId);
        if (!$folder) {
            throw new \DomainException("Dossier introuvable.");
        }

        // 2. TRANSLATION : Slot Virtuel -> Type de Document & Stakeholder
        $type = null;
        $stakeholder = null;

        if ($request->slotId === 'kbis') {
            $type = DocumentType::KBIS;

        } elseif ($request->slotId === 'articles') { // 👈 Mise à jour ici
            $type = DocumentType::ARTICLES_OF_ASSOC;

        } elseif ($request->slotId === 'rbe') {
            $type = DocumentType::RBE;

        } elseif (str_starts_with($request->slotId, 'id_card_')) {
            $type = DocumentType::ID_CARD;
            $stakeholderSlug = str_replace('id_card_', '', $request->slotId);
            $stakeholder = $this->stakeholderRepository->findBySlugId($stakeholderSlug);
        }

        if (!$type) {
            throw new \DomainException("Type de document non reconnu.");
        }

        // 3. RECHERCHE OU CRÉATION (Le fameux Upsert !)
        // On cherche si un document de ce type existe DÉJÀ pour ce dossier (et cette personne)
        $criteria = ['folder' => $folder, 'type' => $type];
        if ($stakeholder) {
            $criteria['stakeholder'] = $stakeholder;
        }

        $document = $this->entityManager->getRepository(KycDocument::class)->findOneBy($criteria);

        // S'il n'existe pas, c'est le premier upload : ON LE CRÉE !
        if (!$document) {
            // S'il n'existe pas, on le crée
            if ($stakeholder) {
                $document = KycDocument::requestForStakeholder($folder, $stakeholder, $type);
            } else {
                $document = KycDocument::requestForCompany($folder, $type);
            }
            $this->entityManager->persist($document);
        } else {
            // S'IL EXISTE DÉJÀ : On supprime physiquement l'ancien fichier !
            if ($document->storagePath) {
                $this->storage->delete($document->storagePath);
            }
        }

        // 4. Sauvegarde physique du fichier
        $directory = 'kyc_folders/' . $folder->slugId;
        $storagePath = $this->storage->store($request->file, $directory);

        // 5. Action Métier : On marque comme uploadé
        $document->markAsUploaded($storagePath);

        // 6. On valide tout en BDD
        $this->entityManager->flush();
    }
}
