<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Client;

use App\Domain\User\Entity\Client;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Webmozart\Assert\Assert;

readonly class CreateClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
    ) {
    }

    public function __invoke(
        string $email,
        string $firstName,
        string $lastName,
        string $workspaceSlugId,
    ): Client {
        $email = strtolower(trim($email));

        $workspace = $this->workspaceRepository->findOneBySlug($workspaceSlugId);
        Assert::notNull($workspace);

        $client = Client::initiate($email, $firstName, $lastName, isActif: true);
        $client->attachToWorkspace($workspace);

        $this->clientRepository->save($client);

        return $client;
    }
}
