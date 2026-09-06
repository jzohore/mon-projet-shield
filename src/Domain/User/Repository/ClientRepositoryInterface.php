<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\Client;
use App\Domain\Workspace\Entity\Workspace;
use Pagerfanta\Pagerfanta;

interface ClientRepositoryInterface
{
    public function findByEmail(string $email): ?Client;

    public function findById(string $id): ?Client;

    /**
     * Sauvegarde le client en base de données.
     */
    public function save(Client $client, bool $flush = true): void;

    /**
     * Supprime définitivement le compte client. Réservé aux comptes sans aucune
     * obligation de conservation (aucun dossier nulle part).
     */
    public function remove(Client $client, bool $flush = true): void;

    public function findByMagicLink(string $magicLink): ?Client;

    /**
     * Clients rattachés à ce cabinet, filtrés par recherche (nom, prénom, email)
     * et par statut d'activité.
     *
     * @param 'recent'|'name'|'status' $sort
     * @param bool|null                $onlyActive null = tous, true = actifs, false = inactifs
     *
     * @return Pagerfanta<Client>
     */
    public function findAllByWorkspace(Workspace $workspace, ?string $search = null, string $sort = 'recent', ?bool $onlyActive = null): Pagerfanta;

    public function findOneBySlugIdAndWorkspace(string $slugId, Workspace $workspace): ?Client;

    /**
     * Nombre de dossiers non supprimés par client, dans ce cabinet.
     *
     * @param list<string> $clientIds
     *
     * @return array<string, int> id du client => nombre de dossiers
     */
    public function folderCountByClients(Workspace $workspace, array $clientIds): array;
}
