<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\Client;

use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Compliance\Enum\FolderType;
use App\Domain\Compliance\Exception\InvalidFolderTypeException;
use App\Domain\Compliance\Factory\ComplianceFolderFactory;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Service\DocumentRequirementEngine;
use App\Domain\User\Entity\Client;
use App\Domain\User\Exception\ClientNotFoundException;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;

/**
 * Crée un dossier de conformité brouillon directement greffé à un client du
 * portefeuille.
 */
readonly class CreateFolderForClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private ComplianceFolderRepositoryInterface $folderRepository,
        private ComplianceFolderFactory $folderFactory,
        private DocumentRequirementEngine $documentRequirementEngine,
        private CurrentWorkspaceProvider $workspaceProvider,
        private CurrentUserProvider $userProvider,
    ) {
    }

    /**
     * @return string slugId du dossier créé
     */
    public function __invoke(string $clientSlugId, string $typeRaw): string
    {
        $type = FolderType::tryFrom($typeRaw);
        if (!$type instanceof FolderType) {
            throw InvalidFolderTypeException::unsupported($typeRaw);
        }

        $workspace = $this->workspaceProvider->getWorkspace();
        $this->userProvider->getUser();

        $client = $this->clientRepository->findOneBySlugIdAndWorkspace($clientSlugId, $workspace);
        if (!$client instanceof Client) {
            throw ClientNotFoundException::withEmail($clientSlugId);
        }

        $folder = $this->folderFactory->createDraft($type, $workspace, $client->email, 'from_client_page');
        $folder->attachClient($client);

        // Le client est déjà connu : on recopie ses informations d'identité sur
        // le dossier pour que l'étape 1 (ManualIndividualStepOneComponent) soit
        // pré-remplie et non bloquante — le cabinet passe directement au DER.
        if ($folder instanceof IndividualFolder) {
            $folder->setClientInfo($client->firstName, $client->lastName, $client->email);
        }

        $this->documentRequirementEngine->generateBaseRequirements($folder);

        $this->folderRepository->save($folder);

        return $folder->slugId;
    }
}
