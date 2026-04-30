<?php

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    /**
     * Sauvegarde ou met à jour un utilisateur en base de données.
     */
    public function save(User $user): void;

    /**
     * Retrouve un utilisateur via son identifiant unique (UUID).
     */
    public function getById(Uuid $id): User;

    /**
     * Indispensable pour le système de login (Symfony Security).
     */
    public function findByEmail(?string $email): ?User;

    public function findBySlug(?string $slug): ?User;

    public function getBySlug(string $slug): User;

    /**
     * @param string $email
     * @return User
     */
    public function getByEmail(string $email): User;

    /**
     * En RegTech, on préfère souvent une méthode "archive" plutôt que "delete",
     * mais au niveau du repository, voici la méthode de suppression standard.
     */
    public function delete(User $user): void;

    public function findByMagicLink(string $magicLink): ?User;

    /**
     * @return Pagerfanta<User>
     */
    public function findMembersForList(Workspace $workspace, ?string $search = null, ?bool $queryEnabled = null): Pagerfanta;

    /**
     * @return array<int, User>
     */
    public function findUsersNeedingReminder(\DateTimeInterface $twoHoursAgo): array;
}
