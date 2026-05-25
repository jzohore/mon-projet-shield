<?php

namespace App\Domain\Compliance\Repository;

use App\Domain\Compliance\Entity\IndividualFolder;

interface IndividualFolderRepositoryInterface extends ComplianceFolderRepositoryInterface
{
    /**
     * Cherche un dossier par l'email du client (utile pour vérifier les doublons).
     */
    public function findByEmail(string $email): IndividualFolder;

    /**
     * @return IndividualFolder[]
     */
    public function findPendingIndividuals(): array;
}
