<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Client;

use App\Domain\User\Entity\Client;
use App\Domain\User\Exception\ClientNotFoundException;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

readonly class AttachExistingClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ClientNotFoundException si le client n'existe pas dans KYSURE
     */
    public function __invoke(string $email, string $workspaceSlugId): Client
    {
        Assert::notEmpty($email, 'L\'email du client est obligatoire.');
        $email = strtolower(trim($email));

        $workspace = $this->workspaceRepository->findOneBySlug($workspaceSlugId);
        Assert::notNull($workspace, sprintf('Le Workspace "%s" est introuvable.', $workspaceSlugId));

        $client = $this->clientRepository->findByEmail($email);

        // 1. Le "Fail Fast" que tu as choisi
        if (!$client instanceof Client) {
            $this->logger->warning('Tentative d\'attachement d\'un client inexistant.', ['email' => $email]);
            throw ClientNotFoundException::withEmail($email);
        }

        // 2. Le client existe, on l'attache
        $client->attachToWorkspace($workspace);
        $this->clientRepository->save($client);

        $this->logger->info('Client existant rattaché au cabinet.', [
            'email' => $email,
            'workspace_slug' => $workspaceSlugId,
        ]);

        return $client;
    }
}
