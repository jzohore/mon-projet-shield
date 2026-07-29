<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\Admin;

interface AdminRepositoryInterface
{
    public function findByEmail(string $email): ?Admin;

    public function findById(string $id): ?Admin;

    /**
     * Sauvegarde le client en base de données.
     */
    public function save(Admin $client, bool $flush = true): void;

    public function findByMagicLink(string $magicLink): ?Admin;
}
