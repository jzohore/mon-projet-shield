<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Application\Compliance\DTO\Response\DraftFolderResponse;
use App\Domain\Compliance\Enum\FolderType;
use App\Domain\Compliance\Exception\InvalidFolderTypeException;
use App\Domain\Compliance\Factory\ComplianceFolderFactory;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Service\DocumentRequirementEngine;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;

readonly class CreateDraftFolderUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $em,
        private CurrentWorkspaceProvider $workspaceProvider,
        private ComplianceFolderFactory $folderFactory,
        private DocumentRequirementEngine $documentRequirementEngine,
        private CurrentUserProvider $currentUserProvider,
    ) {
    }

    public function __invoke(string $typeRaw, string $method): DraftFolderResponse
    {
        // 1. Validation stricte du type grâce à l'Enum
        $type = FolderType::tryFrom($typeRaw);
        if (!$type) {
            throw InvalidFolderTypeException::unsupported($typeRaw);
        }

        $user = $this->currentUserProvider->getUser();
        $workspace = $this->workspaceProvider->getWorkspace();

        $folder = $this->folderFactory->createDraft($type, $workspace, $user->email, $method);
        $this->documentRequirementEngine->generateBaseRequirements($folder);
        // 3. Persistance
        $this->em->save($folder);

        return DraftFolderResponse::fromEntity($folder);
    }
}
