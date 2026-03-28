<?php

namespace App\Application\Kyc\UseCase;

use App\Application\Kyc\DTO\Request\UploadKycDocumentRequest;
use App\Domain\Kyc\Entity\KycDocument;
use App\Domain\Kyc\Enum\DocumentType;
use App\Domain\Kyc\Event\UploadKycDocumentEvent;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Domain\Kyc\Repository\StakeholderRepositoryInterface;
use App\Domain\Port\DocumentStorageInterface;
use App\Infrastructure\Service\ImageOptimizer;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UploadKycDocumentUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentStorageInterface $storage,
        private ValidatorInterface $validator,
        private KycFolderRepositoryInterface $kycFolderRepository,
        private StakeholderRepositoryInterface $stakeholderRepository,
        private EventDispatcherInterface $eventDispatcher,
        private ImageOptimizer $imageOptimizer,
    ) {}

    public function __invoke(UploadKycDocumentRequest $request): void
    {
        $this->validator->validate($request);

        // 1. On récupère le dossier parent
        $folder = $this->kycFolderRepository->findBySlugId($request->folderSlugId);
        if (!$folder) {
            throw new DomainException("Dossier introuvable.");
        }

        // 2. TRANSLATION : Slot Virtuel -> Type de Document & Stakeholder
        $type = null;
        $stakeholder = null;

        if ($request->slotId === 'kbis') {
            $type = DocumentType::KBIS;
        } elseif ($request->slotId === 'articles') {
            $type = DocumentType::ARTICLES_OF_ASSOC;
        } elseif ($request->slotId === 'rbe') {
            $type = DocumentType::RBE;
        } elseif (str_starts_with($request->slotId, 'id_card_')) {
            $type = DocumentType::ID_CARD;
            $stakeholderSlug = str_replace('id_card_', '', $request->slotId);
            $stakeholder = $this->stakeholderRepository->findBySlugId($stakeholderSlug);
        }

        if (!$type) {
            throw new DomainException("Type de document non reconnu.");
        }

        // 3. RECHERCHE OU CRÉATION (Le fameux Upsert !)
        $criteria = ['folder' => $folder, 'type' => $type];
        if ($stakeholder) {
            $criteria['stakeholder'] = $stakeholder;
        }

        $document = $this->entityManager->getRepository(KycDocument::class)->findOneBy($criteria);

        if (!$document) {
            if ($stakeholder) {
                $document = KycDocument::requestForStakeholder($folder, $stakeholder, $type);
            } else {
                $document = KycDocument::requestForCompany($folder, $type);
            }
            $this->entityManager->persist($document);
        } else {
            if ($document->storagePath) {
                $this->storage->delete($document->storagePath);
            }
        }

        // --- 🚀 NOUVELLE ÉTAPE 4 : OPTIMISATION ET SAUVEGARDE ---

        $directory = 'kyc_folders/' . $folder->slugId;
        $fileToStore = $request->file; // Par défaut, on stocke le fichier original (ex: PDF)
        $optimizedTempPath = null;

        $mimeType = $request->file->getMimeType();
        $isImage = in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'], true);

        // Si c'est une image, on l'optimise pour Mindee et Scaleway S3
        if ($isImage) {
            $optimizedTempPath = $this->imageOptimizer->preProcessForOcr($request->file->getRealPath());

            $fileToStore = new UploadedFile(
                $optimizedTempPath,
                $request->file->getClientOriginalName(),
                'image/jpeg',
                null,
                true
            );
        }

        // Envoi vers le S3
        $storagePath = $this->storage->store($fileToStore, $directory);

        // Nettoyage crucial : on supprime le fichier temporaire local généré par Intervention
        if ($optimizedTempPath && file_exists($optimizedTempPath)) {
            unlink($optimizedTempPath);
        }

        // ---------------------------------------------------------

        // 5. Action Métier : On marque comme uploadé
        $document->markAsUploaded($storagePath);

        // 6. On valide tout en BDD
        $this->entityManager->flush();

        // Ce Dispatch d'event est parfait : c'est lui qui va déclencher le Message Messenger pour l'OCR !
        $this->eventDispatcher->dispatch(new UploadKycDocumentEvent($folder, $document));
    }
}
