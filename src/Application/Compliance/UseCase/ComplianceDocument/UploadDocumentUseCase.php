<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument;

use App\Application\Compliance\DTO\Request\UploadDocumentRequest;
use App\Domain\Compliance\Event\DocumentReceivedLocalEvent;
use App\Domain\Compliance\Exception\Document\DocumentNotFoundException;
use App\Domain\Compliance\Exception\Document\InvalidDocumentFolderException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Infrastructure\Service\StorageService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

readonly class UploadDocumentUseCase
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $repository,
        private ComplianceFolderRepositoryInterface $complianceFolderRepository,
        private EventDispatcherInterface $eventDispatcher,
        private StorageService $storageService,
        private string $tempStorageDir,
    ) {
    }

    public function __invoke(UploadDocumentRequest $request): void
    {
        $documentUuid = Uuid::fromString($request->documentId);
        $document = $this->repository->findById($documentUuid);

        $folderUuid = Uuid::fromString($request->folderId);
        $folder = $this->complianceFolderRepository->findById($folderUuid);

        if (!$document instanceof \App\Domain\Compliance\Entity\ComplianceDocument) {
            throw DocumentNotFoundException::withId($request->documentId);
        }

        if ($document->folder !== $folder) {
            throw InvalidDocumentFolderException::forDocument($request->documentId, $folder->slugId);
        }

        $oldStoragePath = $document->storagePath; // À conserver pour la suppression asynchrone future

        // 1. Stockage Local Temporaire (Instantané)
        $niceName = sprintf(
            '%s-%s-%s.%s',
            $folder->reference, // Ex: "DOS-2026-05"
            $document->type->value, // Ex: "id_card"
            uniqid(),
            $request->file->guessExtension()
        );

        $localTempPath = $this->storageService->storeLocally($request->file, $this->tempStorageDir, 'compliance_fol_');

        // 2. Mise à jour de l'état (On enregistre le chemin local temporaire en attendant le S3)
        $document->markAsProcessed();
        $this->repository->save($document);

        $this->eventDispatcher->dispatch(new DocumentReceivedLocalEvent(
            complianceDocument: $document,
            complianceFolder: $folder,
            localTempPath: $localTempPath,
            mimeType: $request->file->getClientMimeType(),
            originalName: $niceName,
            size: (int) filesize($localTempPath),
            oldStoragePath: $oldStoragePath
        ));
    }
}
