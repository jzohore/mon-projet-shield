<?php

namespace App\Infrastructure\Storage;

use App\Domain\Port\DocumentStorageInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class ScalewayS3DocumentStorage implements DocumentStorageInterface
{
    public function __construct(
        // Symfony va automatiquement injecter le disque S3 "kyc.storage"
        // configuré dans flysystem.yaml grâce au nom de la variable $kycStorage
        private FilesystemOperator $kycStorage,
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
    ) {}

    public function store(UploadedFile $file, string $directory): string
    {
        // 1. Génération du nom sécurisé (Identique à ta version locale)
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $targetPath = $directory . '/' . $fileName;

        try {
            // 2. On ouvre le fichier en mode "stream" (lecture continue)
            // C'est VITAL pour S3 : ça évite de charger un gros PDF entièrement dans la RAM de ton serveur Hetzner
            $stream = fopen($file->getPathname(), 'r');

            // 3. Flysystem envoie le flux directement sur Scaleway S3
            $this->kycStorage->writeStream($targetPath, $stream);

            // On ferme proprement le flux
            if (is_resource($stream)) {
                fclose($stream);
            }
        } catch (FilesystemException $e) {
            $this->logger->error('Impossible d\'écrire le fichier sur Scaleway S3.', [
                'path' => $targetPath,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Erreur lors de la sauvegarde du document KYC sur le cloud sécurisé.");
        }

        return $targetPath;
    }

    public function delete(string $path): void
    {
        try {
            // 1. Flysystem vérifie si le fichier existe sur S3
            if ($this->kycStorage->fileExists($path)) {

                // 2. Suppression via l'API S3
                $this->kycStorage->delete($path);

                $this->logger->info("Ancien document KYC supprimé physiquement de Scaleway S3 : " . $path);
            }
        } catch (FilesystemException $e) {
            $this->logger->error("Impossible de supprimer l'ancien document KYC sur S3 : " . $path, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getTemporaryUrl(string $path): string
    {
        // On demande à Flysystem (via le client S3) de générer un lien public temporaire
        // Valable 20 minutes ici
        return $this->kycStorage->temporaryUrl($path, new \DateTime('+20 minutes'));
    }
}
