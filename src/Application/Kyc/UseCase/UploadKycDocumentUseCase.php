<?php

declare(strict_types=1);

namespace App\Application\Kyc\UseCase;

use App\Application\Kyc\DTO\Request\UploadKycDocumentRequest;
use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Kyc\Entity\KycDocument;
use App\Domain\Kyc\Entity\KycFolder;
use App\Domain\Kyc\Entity\Stakeholder;
use App\Domain\Kyc\Event\KycDocumentReceivedLocalEvent;
use App\Domain\Kyc\Repository\KycDocumentRepositoryInterface;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Domain\Kyc\Repository\StakeholderRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UploadKycDocumentUseCase
{
    public function __construct(
        private KycDocumentRepositoryInterface $kycDocumentRepository,
        private ValidatorInterface $validator,
        private KycFolderRepositoryInterface $kycFolderRepository,
        private StakeholderRepositoryInterface $stakeholderRepository,
        private EventDispatcherInterface $eventDispatcher,
        private string $tempStorageDir, // Injection du paramètre (ex: '%kernel.project_dir%/var/uploads/kyc_temp')
    ) {
    }

    public function __invoke(UploadKycDocumentRequest $request): void
    {
        $this->validator->validate($request);

        $folder = $this->kycFolderRepository->findBySlugId($request->folderSlugId);
        if (!$folder instanceof KycFolder) {
            throw new \DomainException('Dossier introuvable.');
        }

        [$type, $stakeholder] = $this->resolveDocumentContext($request->slotId);

        $document = $this->getOrCreateDocument($folder, $type, $stakeholder);
        $oldStoragePath = $document->storagePath; // À conserver pour la suppression asynchrone future

        // 1. Stockage Local Temporaire (Instantané)
        $localTempPath = $this->storeLocally($request->file);

        // 2. Mise à jour de l'état (On enregistre le chemin local temporaire en attendant le S3)
        $document->markAsUploaded($localTempPath);
        $this->kycDocumentRepository->save($document);

        // 3. Dispatch de l'Event Métier
        $this->eventDispatcher->dispatch(new KycDocumentReceivedLocalEvent(
            kycDocument: $document,
            kycFolder: $folder,
            localTempPath: $localTempPath,
            mimeType: $request->file->getClientMimeType(),
            originalName: $request->file->getClientOriginalName(),
            oldStoragePath: $oldStoragePath
        ));
    }

    /**
     * @return array{0: DocumentType, 1: Stakeholder|null}
     */
    private function resolveDocumentContext(string $slotId): array
    {
        if (str_starts_with($slotId, 'id_card_')) {
            $stakeholderSlug = str_replace('id_card_', '', $slotId);
            $stakeholder = $this->stakeholderRepository->findBySlugId($stakeholderSlug);

            if (!$stakeholder instanceof Stakeholder) {
                throw new \DomainException('Intervenant introuvable.');
            }

            return [DocumentType::ID_CARD, $stakeholder];
        }

        $type = match ($slotId) {
            'kbis' => DocumentType::KBIS,
            'articles_of_assoc' => DocumentType::ARTICLES_OF_ASSOC,
            'rbe' => DocumentType::RBE,
            default => throw new \DomainException(sprintf('Type de document non reconnu: %s', $slotId)),
        };

        return [$type, null];
    }

    private function getOrCreateDocument(KycFolder $folder, DocumentType $type, ?Stakeholder $stakeholder): KycDocument
    {
        $document = $this->kycDocumentRepository->findOneByContext($folder, $type, $stakeholder);

        if (!$document instanceof KycDocument) {
            return $stakeholder instanceof Stakeholder
                ? KycDocument::requestForStakeholder($folder, $stakeholder, $type)
                : KycDocument::requestForCompany($folder, $type);
        }

        return $document;
    }

    private function storeLocally(UploadedFile $file): string
    {
        if (!is_dir($this->tempStorageDir)) {
            mkdir($this->tempStorageDir, 0o755, true);
        }

        $extension = $file->guessExtension() ?? 'bin';
        $filename = uniqid('kyc_tmp_', true) . '.' . $extension;

        $file->move($this->tempStorageDir, $filename);

        return $this->tempStorageDir . \DIRECTORY_SEPARATOR . $filename;
    }
}
