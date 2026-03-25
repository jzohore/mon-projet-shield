<?php

namespace App\Application\Kyc\Port;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface DocumentStorageInterface
{
    /**
     * Stocke un fichier et retourne son chemin d'accès (Storage Path)
     * * @param UploadedFile $file Le fichier physique
     * @param string $directory Le dossier cible (ex: l'ID du dossier KYC)
     * @return string Le chemin relatif ou l'URL du fichier enregistré
     */
    public function store(UploadedFile $file, string $directory): string;

    /**
     * Supprime physiquement un fichier du stockage
     * @param string $path Le chemin relatif du fichier (ex: kyc_folders/xyz/doc.pdf)
     */
    public function delete(string $path): void;
}
