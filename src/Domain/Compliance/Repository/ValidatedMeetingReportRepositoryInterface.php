<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Repository;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\ValidatedMeetingReport;
use Symfony\Component\Uid\Uuid;

interface ValidatedMeetingReportRepositoryInterface
{
    /**
     * Persiste un rapport d'entretien validé.
     *
     * Volontairement pas de `remove()` : un rapport validé est une pièce
     * d'audit, il se révoque ({@see ValidatedMeetingReport::revoke()}), il ne
     * se supprime pas.
     */
    public function save(ValidatedMeetingReport $report, bool $flush = true): void;

    public function findById(Uuid|string $id): ?ValidatedMeetingReport;

    public function findBySlugId(string $slugId): ?ValidatedMeetingReport;

    /**
     * La version actuellement en vigueur pour ce dossier (non révoquée),
     * ou `null` si le dossier n'a aucun rapport validé.
     */
    public function findInForceByFolder(ComplianceFolder $complianceFolder): ?ValidatedMeetingReport;

    /**
     * Le plus grand numéro de version émis pour ce dossier (0 si aucun).
     * La prochaine validation utilisera `+ 1`.
     */
    public function findLatestVersionNumber(ComplianceFolder $complianceFolder): int;

    /**
     * Historique complet des rapports validés du dossier, du plus récent au
     * plus ancien.
     *
     * @return ValidatedMeetingReport[]
     */
    public function findAllByFolder(ComplianceFolder $complianceFolder): array;
}
