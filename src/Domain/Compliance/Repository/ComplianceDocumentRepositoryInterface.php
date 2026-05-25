<?php

namespace App\Domain\Compliance\Repository;

use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Kyc\Enum\DocumentType;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Component\Uid\Uuid;

interface ComplianceDocumentRepositoryInterface
{
    /**
     * Récupère un document précis (pour le téléchargement ou la validation).
     */
    public function findById(Uuid|string $id): ComplianceDocument;

    /**
     * Récupère tous les documents ayant un statut spécifique (ex: 'PENDING' pour les agents qui vérifient).
     * @return ComplianceDocument[]
     * @param string $status
     * @param Workspace $workspace
     */
    public function findByStatus(string $status, Workspace $workspace): array;

    /**
     * Cherche si un document d'un certain type existe déjà pour un dossier (optimisation).
     */
    public function existsForFolder(Uuid|string $folderId, DocumentType $type): bool;
}
