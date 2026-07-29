<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Exception\Document\DocumentNotFoundException;
use App\Domain\Compliance\Exception\Document\InvalidDocumentFolderException;
use App\Domain\Compliance\Exception\Document\MandatoryDocumentDeletionException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;

readonly class RemoveDocumentUseCase
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $repository,
    ) {
    }

    public function __invoke(string $documentId, ComplianceFolder $folder): void
    {
        $document = $this->repository->findById($documentId);

        if (!$document instanceof \App\Domain\Compliance\Entity\ComplianceDocument) {
            throw DocumentNotFoundException::withId($documentId);
        }

        if ($document->folder !== $folder) {
            throw InvalidDocumentFolderException::forDocument($documentId, $folder->slugId);
        }

        if ($document->isMandatory) {
            // Assure-toi d'avoir une méthode de type getLabel() ou name sur l'enum DocumentType
            throw MandatoryDocumentDeletionException::forType($document->type->getLabel());
        }

        $this->repository->remove($document);
    }
}
