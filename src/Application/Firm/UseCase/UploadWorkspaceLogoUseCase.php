<?php

declare(strict_types=1);

namespace App\Application\Firm\UseCase;

use App\Application\Firm\DTO\Request\UploadLogoRequest;
use App\Domain\Port\DocumentStorageInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Webmozart\Assert\Assert;

readonly class UploadWorkspaceLogoUseCase
{
    public function __construct(
        private CurrentWorkspaceProvider $workspaceProvider,
        private WorkspaceRepositoryInterface $repository,
        private DocumentStorageInterface $storage, // On réutilise ton abstraction !
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UploadLogoRequest $request): void
    {
        $workspace = $this->workspaceProvider->getWorkspace();
        $profile = $workspace->regulatoryProfile;

        Assert::notNull($profile);

        $file = $request->logoFile;

        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            return;
        }

        $oldStoragePath = $profile->filename;

        try {
            // 1. Suppression de l'ancien logo sur S3 (Nettoyage)
            if ($oldStoragePath) {
                try {
                    $this->storage->delete($oldStoragePath);
                } catch (\Throwable $e) {
                    $this->logger->warning('Impossible de supprimer l\'ancien logo', [
                        'workspace_id' => $workspace->id,
                        'path' => $oldStoragePath,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 2. Génération d'un nom propre et unique
            $originalName = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
            $safeName = $this->slugger->slug($originalName)->lower()->toString();
            $niceName = sprintf('logo-%s-%s.%s', $safeName, uniqid(), $file->guessExtension());

            // 3. Upload S3 (Synchrone)
            // On utilise la même logique que ton worker KYC, mais en direct
            $directory = sprintf('workspaces/%s/branding', $workspace->id);

            // J'assume que ta méthode store() gère les objets UploadedFile natifs
            $finalStoragePath = $this->storage->store($file, $directory);

            // 4. Persistance Domaine
            $profile->updateStoragePath($finalStoragePath, $niceName);
            $this->repository->save($workspace);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de l\'upload du logo : ' . $e->getMessage());
            throw $e; // On remonte l'erreur pour afficher un message flash à l'utilisateur
        }
    }
}
