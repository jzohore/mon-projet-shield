<?php

namespace App\Domain\Support\Repository;

use App\Application\Support\DTO\Response\SupportNotificationStats;
use App\Domain\Support\Entity\SupportThread;
use App\Domain\Support\Enum\SupportThreadStatus;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;

interface SupportThreadRepositoryInterface
{
    /**
     * Recherche la conversation "En cours" (ouverte) d'un utilisateur dans un espace de travail précis.
     */
    public function findActiveThreadForUser(Workspace $workspace, User $user): ?SupportThread;

    /**
     * Récupère un ticket par son identifiant unique.
     */
    public function findById(Uuid $id): ?SupportThread;

    /**
     * Ajoute le thread à la collection (persistence).
     * Note: Dans un DDD strict, le flush() (la transaction) est géré au niveau applicatif (Use Case ou Middleware),
     * pas dans le repository.
     */
    public function save(SupportThread $thread): void;

    /**
     * Retire le thread de la collection.
     */
    public function delete(SupportThread $thread): void;

    /**
     * @param Workspace $workspace
     * @param User $user
     * @return SupportNotificationStats
     */
    public function getNotificationStats(Workspace $workspace, User $user): SupportNotificationStats;

    /**
     * @return int
     */
    public function countAllOpenTickets(): int;

    /**
     * @param string|null $search
     * @param SupportThreadStatus|null $statusFilter
     * @return Pagerfanta<SupportThread>
     */
    public function getPaginatedSupport(?string $search = null, ?SupportThreadStatus $statusFilter = null): Pagerfanta;

    public function refresh(SupportThread $thread): void;

    /**
     * Récupère les tickets ouverts, sans avertissement, inactifs depuis X temps.
     *
     * @param \DateTimeInterface $threshold
     * @return SupportThread[]
     */
    public function findInactiveThreadsForWarning(\DateTimeInterface $threshold): array;

    /**
     * Récupère les tickets ouverts, DEJA avertis, inactifs depuis X temps après l'avertissement.
     *
     * @param \DateTimeInterface $threshold
     * @return SupportThread[]
     */
    public function findThreadsPendingClosure(\DateTimeInterface $threshold): array;
}
