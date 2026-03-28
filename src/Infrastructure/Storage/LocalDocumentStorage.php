<?php

namespace App\Infrastructure\Storage;

use App\Application\Kyc\Port\DocumentStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class LocalDocumentStorage implements DocumentStorageInterface
{
    public function __construct(
        // On injectera le chemin racine via le services.yaml (ex: /var/www/mon-saas/var/uploads)
        private string $baseUploadDirectory,
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
    ) {}

    public function store(UploadedFile $file, string $directory): string
    {
        // 1. Nettoyage du nom de fichier original (sécurité)
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);

        // 2. Création d'un nom unique et non devinable (ex: kbis-entreprise-65a4f3b2.pdf)
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // 3. Le chemin absolu où le fichier sera physiquement stocké sur le serveur
        $targetDirectory = $this->baseUploadDirectory . '/' . $directory;

        try {
            // 4. Symfony déplace le fichier du dossier temporaire (tmp) vers notre dossier sécurisé.
            // La méthode move() va créer les sous-dossiers automatiquement s'ils n'existent pas !
            $file->move($targetDirectory, $fileName);
        } catch (FileException $e) {
            // Si le disque est plein ou problème de permissions système
            $this->logger->error('Impossible d\'écrire le fichier sur le disque.', [
                'directory' => $targetDirectory,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Erreur lors de la sauvegarde du document KYC.");
        }

        // 5. On retourne UNIQUEMENT le chemin relatif pour la base de données.
        // Exemple: "kyc_folders/folder_01H.../kbis-entreprise-65a4f3b2.pdf"
        // Ça nous permet de changer de serveur ou de dossier racine plus tard sans casser la BDD.
        return $directory . '/' . $fileName;
    }

    public function delete(string $path): void
    {
        // 1. On reconstruit le chemin absolu sur le serveur
        $absolutePath = $this->baseUploadDirectory . '/' . $path;

        // 2. On vérifie que le fichier existe bien avant de le supprimer
        if (file_exists($absolutePath) && is_file($absolutePath)) {
            try {
                unlink($absolutePath); // 👈 C'est ici que la magie de suppression opère !
                $this->logger->info("Ancien document KYC supprimé physiquement : " . $path);
            } catch (\Exception $e) {
                $this->logger->error("Impossible de supprimer l'ancien document KYC : " . $path, [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function getTemporaryUrl(string $path): string
    {
        return $path;
    }
}
