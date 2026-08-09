<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\OnboardingCompliance;

use App\Application\Compliance\DTO\Request\SubmitFlashOnboardingRequest;
use App\Application\Compliance\DTO\Response\DraftFolderResponse;
use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Compliance\Enum\FolderType;
use App\Domain\Compliance\Exception\InvalidFolderTypeException;
use App\Domain\Compliance\Factory\ComplianceFolderFactory;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Service\DocumentRequirementEngine;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\Uid\Uuid;

readonly class SubmitFlashOnboardingUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $repository,
        private ComplianceFolderFactory $folderFactory,
        private DocumentRequirementEngine $documentRequirementEngine,
        private WorkspaceRepositoryInterface $workspaceRepository,
    ) {
    }

    public function __invoke(SubmitFlashOnboardingRequest $request, string $typeRaw): DraftFolderResponse
    {
        $type = FolderType::tryFrom($typeRaw);
        if (!$type) {
            throw InvalidFolderTypeException::unsupported($typeRaw);
        }

        $workspaceUuid = Uuid::fromString($request->workspaceUuid);
        $workspace = $this->workspaceRepository->getById($workspaceUuid);

        $folder = $this->folderFactory->createDraft(
            type: $type,
            workspace: $workspace,
            email: $request->email,
            method: 'request'
        );

        if ($folder instanceof IndividualFolder) {
            $folder->setClientInfo(
                firstName: $request->firstName,
                lastName: $request->lastName,
                email: $request->email
            );
        }

        $this->documentRequirementEngine->generateBaseRequirements($folder);

        // 4. Persistance
        $this->repository->save($folder);

        // 5. Déclenchement de l'événement (pour Mercure / Notification CGP)
        // $this->eventDispatcher->dispatch(new FolderCreatedViaFlashEvent($folder->getId()));

        return DraftFolderResponse::fromEntity($folder);
    }
}
