<?php

namespace App\Domain\Compliance\Repository;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Component\Uid\Uuid;

interface ComplianceFolderRepositoryInterface
{
    /**
     * Sauvegarde ou met à jour un dossier.
     * @param ComplianceFolder $folder
     * @param bool $flush
     * @return void
     */
    public function save(ComplianceFolder $folder, bool $flush = true): void;

    /**
     * Supprime un dossier.
     * @param ComplianceFolder $folder
     * @param bool $flush
     * @return void
     */
    public function remove(ComplianceFolder $folder, bool $flush = true): void;

    /**
     * Récupère un dossier par son identifiant unique.
     * @param Uuid|string $id
     */
    public function findById(Uuid|string $id): ComplianceFolder;

    /**
     * Récupère un dossier par sa référence interne (ex: KYC-123456).
     * @param string $reference
     */
    public function findByReference(string $reference): ComplianceFolder;

    /**
     * Liste tous les dossiers actifs d'un espace de travail.
     * @return ComplianceFolder[]
     * @param Workspace $workspace
     */
    public function findAllActiveByWorkspace(Workspace $workspace): array;

    public function countDraftsForWorkspace(Workspace $workspace): int;
}
