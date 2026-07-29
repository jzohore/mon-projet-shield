<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\Client;

interface ClientRepositoryInterface
{
    public function findByEmail(string $email): ?Client;

    public function findById(string $id): ?Client;

    /**
     * Sauvegarde le client en base de données.
     */
    public function save(Client $client, bool $flush = true): void;

    public function findByMagicLink(string $magicLink): ?Client;
}
