<?php

namespace App\Domain\Compliance\Repository;

use App\Domain\Compliance\Entity\BusinessFolder;

interface BusinessFolderRepositoryInterface extends ComplianceFolderRepositoryInterface
{
    /**
     * Cherche un dossier par le numéro d'immatriculation (SIRET, SIREN, etc.).
     */
    public function findByRegistrationNumber(string $registrationNumber): BusinessFolder;

    /**
     * @return BusinessFolder[]
     */
    public function findPendingBusinesses(): array;
}
