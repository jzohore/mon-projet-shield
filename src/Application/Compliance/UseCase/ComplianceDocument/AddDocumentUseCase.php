<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument;

use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Compliance\Exception\Document\DocumentExistInFolderException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;

readonly class AddDocumentUseCase
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $repository,
    ) {
    }

    public function __invoke(DocumentType $type, ComplianceFolder $folder): ComplianceDocument
    {
        if ($this->repository->existsForFolderAndType($folder, $type)) {
            throw DocumentExistInFolderException::withName($type->name);
        }

        $document = ComplianceDocument::createExpected($folder, $type, false);

        $this->repository->save($document);

        return $document;
    }
}
