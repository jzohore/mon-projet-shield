<?php

namespace App\Domain\Tracking\Repository;

use App\Domain\Tracking\Entity\ClickLog;

interface ClickLogRepositoryInterface
{
    /**
     * Persiste un log en base de données.
     */
    public function save(ClickLog $clickLog, bool $flush = false): void;

    /**
     * Supprime un log (rare pour du tracking, mais nécessaire pour l'interface).
     */
    public function remove(ClickLog $clickLog, bool $flush = false): void;

    /**
     * Récupère un log par son SlugId (clog_...).
     */
    public function findBySlug(string $slugId): ?ClickLog;

    /**
     * Retourne les statistiques de clics groupées par élément pour une période donnée.
     * Utile pour ton dashboard interne Kysure.
     * @return array<string, int>
     */
    public function getStatsByElement(\DateTimeImmutable $since): array;

    /**
     * Compte le nombre total de clics provenant d'une source spécifique (ex: linkedin).
     */
    public function countBySource(string $source): int;
}
